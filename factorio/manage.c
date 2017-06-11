/*
 * LaunchServer.c
 *
 *  Created on: Nov 4, 2016
 *      Author: zackman0010
 * Description: This program, coded for 3Ra Gaming, will serve as a communications link,
 *              allowing multiple Factorio servers, a Discord bot, and our webserver to all communicate with each other
 */

#include <stdlib.h>
#include <stdio.h>
#include <unistd.h>
#include <string.h>
#include <errno.h>
#include <signal.h>
#include <time.h>
#include <pthread.h>
#include <sys/types.h>
#include <sys/wait.h>

//Create a struct to hold the data for each server, including name and pipe FDs
struct ServerData {
	int serverid; //The index of the server, bot will be 0
	int pid; //The process id of the server, used to send SIGINT (CTRL-C)
	char *name; //Server Name
	int input; //Overwritten STDIN
	int output; //Overwritten STDOUT
	pthread_mutex_t mutex; //Thread safety
	char * status; //Started or Stopped
	char * logfile; //Location of logfile to write to
	char * chatlog; //Location of chatlog to write to
	pthread_mutex_t chat_mutex; //Mutex for chatlog protection
};

//Set up global variables
struct ServerData **server_list;
pthread_t *thread_list;
pthread_attr_t thread_attr;
int servers;
int currently_running;
int bot_ready;

//Function declarations
struct ServerData *find_server(char *);
char * send_threaded_chat(char *, char *);
char * log_chat(char *, char *);
char * get_server_status(char *);
void * input_monitoring(void *);
char * launch_server(char *, char **, char *);
char * start_server(char *, char *);
char * stop_server(char *);
void stop_all_servers();
void launch_bot();
void server_crashed(struct ServerData *);
void * bot_ready_watch(void *);
void * heartbeat();

//Find server with given name
struct ServerData * find_server(char * name) {
	for (int i = 0; i < servers; i++) {
		if (strcmp(server_list[i]->name,name) == 0) {
			//If server exists, return the correct server struct
			return server_list[i];
		}
	}
	//If server does not exist, return a fake server struct
	return (struct ServerData *) NULL;
}

//Function to write data using thread safe methods
char * send_threaded_chat(char * name, char * message) {
	//Get the server that data is being sent to
	struct ServerData * sendto;
	if (strcmp(name, "bot") == 0) {
		sendto = server_list[0];
	} else {
		sendto = find_server(name);
		if (sendto == NULL) return "Server Not Running";
		if (strcmp(sendto->status, "Stopped") == 0) return "Server Not Running";
	}

	//Attempt to lock the mutex - If another thread is currently writing to this place, the code will wait here
	pthread_mutex_lock(&sendto->mutex);

	//In case of crashes
	if (strcmp(sendto->status, "Stopped") == 0) return "Server Crashed";

	//Write data, with added error checking for crash detection
	FILE *output = fdopen(dup(sendto->input), "a");
	fputs(message, output);
	if (fclose(output) == EOF && errno == EPIPE) {
		server_crashed(sendto);
		return "Failed";
	}

	//Unlock the mutex so that another thread can send data to this server
	pthread_mutex_unlock(&sendto->mutex);

	return "Successful";
}

//Function to log chat using thread safe methods
char * log_chat(char * name, char * message) {
	//Get the server that data is being sent to
	struct ServerData * sendto;
	if (strcmp(name, "bot") == 0) {
		sendto = server_list[0];
	} else {
		sendto = find_server(name);
		if (sendto == NULL) return "Server Not Running";
		if (strcmp(sendto->status, "Stopped") == 0) return "Server Not Running";
	}

	//Strip trailing characters if present
	if (message[strlen(message) - 1] == '\n') message[strlen(message) - 1] = '\0';

	//Set up the timestamp
	//YYYY-MM-DD HH:MM:SS
	time_t current_time = time(NULL);
	struct tm *time_data = localtime(&current_time);
	char *timestamp = (char *) malloc((strlen("YYYY-MM-DD HH:MM:SS") + 3) * sizeof(char));
	sprintf(timestamp, "%04d-%02d-%02d %02d:%02d:%02d", time_data->tm_year + 1900, time_data->tm_mon + 1, time_data->tm_mday, time_data->tm_hour, time_data->tm_min, time_data->tm_sec);

	//Set up timestamped message, also prefixes chats coming in from servers with [CHAT]
	char *output_message = (char *) malloc((strlen(timestamp) + strlen(message) + 13)*sizeof(char));
	int chat = 1;
	if (strstr(message, "[DISCORD]") != NULL) chat = 0;
	if (strstr(message, "[WEB]") != NULL) {
		//If this message comes from the webserver, send it to the bot
		if (message[strlen(message) - 1] == ')' && message[strlen(message) - 2] == '"') {
			message[strlen(message) - 1] = '\0';
			message[strlen(message) - 1] = '\0';
		}
		char *bot_message = (char *) malloc((strlen(name) + strlen(message) + 5)*sizeof(char));
		sprintf(bot_message, "%s$%s\n", name, message);
		send_threaded_chat("bot", bot_message);
		free(bot_message);
		chat = 0;
	}
	if (strstr(message, "[PUPDATE]") != NULL) chat = 0;
	if (chat == 1) sprintf(output_message, "%s [CHAT] %s\r\n", timestamp, message);
	else sprintf(output_message, "%s %s\r\n", timestamp, message);
	free(timestamp);

	//Attempt to lock the mutex - If another thread is currently writing to this place, the code will wait here
	pthread_mutex_lock(&sendto->chat_mutex);

	//Write data
	FILE *output = fopen(sendto->chatlog, "a");
	fputs(output_message, output);
	fclose(output);

	//Unlock the mutex so that another thread can send data to this server
	pthread_mutex_unlock(&sendto->chat_mutex);

	//Free memory
	free(output_message);

	return "Successful";
}

//Get the status of a server
char * get_server_status(char * name) {
	//Check to see if a server is running or not
	struct ServerData * server = find_server(name);
	if (server == NULL) return "Server Does Not Exist";
	else if (strcmp(server->status, "Stopped") == 0) return "Server Stopped";
	else if (strcmp(server->status, "Restarting") == 0) return "Bot Restarting";
	else return "Server Running";
}

//Function to be called by threads in order to monitor input
void * input_monitoring(void * server_ptr) {
	//Declare variables used in input parsing
	char *message;
	char *new_data;
	char *actual_server_name;
	char *output;
	char *token;
	char *delim = ",\n\t";
	int i;
	char **chat_args;
	char **player_args;
	char *command;
	char *force_name;
	char *message_to_send;
	char *player_announcement;

	//Declare server variables
	struct ServerData *server = (struct ServerData *) server_ptr;
	int separator_index;
	char *servername = (char *) malloc(sizeof(char));
	char *data = (char *) malloc(2001*sizeof(char));
	FILE *input = fdopen(server->output, "r"); //Begin monitoring the pipe
	FILE *logfile;
	if (strcmp(server->name, "bot") != 0) {
		//If Factorio server, create the logfile
		logfile = fopen(server->logfile, "a");
	}
	while (1) {
		if (fgets(data, 2001, input) == NULL || data[0] == '\n') {
			//This should only get called when the server shuts down
			break;
		}

		if (strcmp(server->name, "bot") != 0 && strstr(data, " [CHAT] ") == NULL && strstr(data, " (shout):") == NULL) {
			output = (char *) malloc((strlen(data) + 5)*sizeof(char));
			sprintf(output, "%s\r\n", data);
			fputs(output, logfile);
			fflush(logfile);
			free(output);
		}

		if (strchr(data,'$') != NULL && ((strstr(data, " [CHAT] ") == NULL && strstr(data, " (shout):") == NULL) || strcmp(server->name, "bot") == 0)) {
			//Handles the rare occasion a chat message will have a '$' inside it
			separator_index = strchr(data,'$') - data;
			data[separator_index] = '\0';
			servername = (char *) realloc(servername, (separator_index + 4)*sizeof(char));
			strcpy(servername, data);
			new_data = (char *) malloc((strlen(data + separator_index + 1) + 3)*sizeof(char));
			strcpy(new_data, data + separator_index + 1);
			if (strchr(new_data,'\n') != NULL) new_data[strchr(new_data,'\n') - new_data] = '\0';
			if (strcmp(servername, "restart") == 0 && strcmp(server->name, "bot") == 0) {
				//Bot wants to restart
				pthread_mutex_lock(&server->mutex); //Lock the mutex to prevent the bot from being used before it's ready
				bot_ready = 0;
				server->status = "Restarting";
				kill(server->pid, SIGINT);
				waitpid(server->pid, NULL, 0);
				fclose(input);
				close(server->input);
				launch_bot();
				input = fdopen(server->output, "r");
				pthread_mutex_unlock(&server->mutex);
			} else if (strcmp(servername, "ready") == 0 && strcmp(server->name, "bot") == 0) {
				//Bot startup is complete, it is ready to continue
				bot_ready = 1;
			} else if (strcmp(servername, "DEBUG") == 0) {
				//Handle debug messages
				fprintf(stderr, "%s\n", new_data);
			} else if (strcmp(servername, "chat") == 0) {
				//Handle Articulating's Chat Program
				chat_args = (char **) malloc(4*sizeof(char *));
				i = 0;

				token = strtok(new_data, delim);
				chat_args[i++] = token;
				while (token != NULL) {
					token = strtok(NULL, delim);
					chat_args[i++] = token;
				}

				message = (char *) malloc((strlen("/silent-command push_message('','','')") + strlen(chat_args[0]) + strlen(chat_args[1]) + strlen(chat_args[2]) + 2)*sizeof(char));
				sprintf(message, "/silent-command push_message('%s','%s','%s')\n", chat_args[0], chat_args[1], chat_args[2]);
				free(chat_args);
				for (int i = 0; i < servers; i++) {
					if (strcmp(server_list[i]->status, "Started") == 0) send_threaded_chat(server_list[i]->name, message);
				}
				free(message);
			} else if (strcmp(servername, "PLAYER") == 0) {
				//This is a player update, used for the bot to keep track of PvP Player Teams
				message = (char *) malloc((strlen("PLAYER$") + strlen(server->name) + strlen("$") + strlen(new_data) + 3)*sizeof(char));
				sprintf(message, "PLAYER$%s$%s\n", server->name, new_data);

				i = 0;
				player_args = (char **) malloc(5*sizeof(char *));
				player_announcement = (char *) malloc((strlen(new_data) + 75)*sizeof(char));

				token = strtok(new_data, delim);
				player_args[i++] = token;
				while (token != NULL) {
					token = strtok(NULL, delim);
					player_args[i++] = token;
				}

				if (strcmp(player_args[0], "join") == 0) {
					sprintf(player_announcement, "[PUPDATE] %s has joined the server [%s]", player_args[2], player_args[3]);
				} else if (strcmp(player_args[0], "leave") == 0) {
					sprintf(player_announcement, "[PUPDATE] %s has left the server [%s]", player_args[2], player_args[3]);
				} else if (strcmp(player_args[0], "force") == 0) {
					sprintf(player_announcement, "[PUPDATE] %s has changed forces to %s", player_args[2], player_args[3]);
				} else if (strcmp(player_args[0], "die") == 0) {
					sprintf(player_announcement, "[PUPDATE] %s was killed [%s]", player_args[2], player_args[3]);
				} else if (strcmp(player_args[0], "respawn") == 0) {
					sprintf(player_announcement, "[PUPDATE] %s has respawned [%s]", player_args[2], player_args[3]);
				} else if (strcmp(player_args[0], "update") != 0) {
					free(player_args);
					free(player_announcement);
					free(message);
					continue;
				}

				log_chat(server->name, player_announcement);

				free(player_args);
				free(player_announcement);

				send_threaded_chat("bot", message);
				free(message);
			} else if (strcmp(servername, "admin") == 0) {
				if (strcmp(server->name, "bot") == 0) {
					//Bot is sending a command or announcement to a server
					separator_index = strchr(new_data, '$') - new_data;
					new_data[separator_index] = '\0';
					actual_server_name = (char *) malloc((strlen(new_data)+3)*sizeof(char));
					strcpy(actual_server_name, new_data);
					command = (char *) malloc((strlen(new_data + separator_index + 1) + 4)*sizeof(char));
					strcpy(command, new_data + separator_index + 1);
					strcat(command, "\n");
					if (strcmp(actual_server_name,"all") == 0) {
						for (int i = 1; i < servers; i++) {
							send_threaded_chat(server_list[i]->name, command);
						}
					} else {
						send_threaded_chat(actual_server_name, command);
					}
					free(actual_server_name);
					free(command);
				} else {
					//Admin Warning System is being sent back to the bot
					message = (char *) malloc((strlen("admin$") + strlen(server->name) + strlen(new_data) + 6)*sizeof(char));
					sprintf(message, "admin$%s$%s\n", server->name, new_data);
					send_threaded_chat("bot", message);
					free(message);
				}
			} else if (strcmp(servername, "output") == 0) {
				message = (char *) malloc((strlen("output$()") + strlen(server->name) + strlen(new_data) + 5)*sizeof(char));
				sprintf(message, "output$(%s)%s\n", server->name, new_data);
				send_threaded_chat("bot", message);
				free(message);
			} else if (strcmp(servername, "query") == 0) {
				message = (char *) malloc((strlen("query$") + strlen(new_data) + 6)*sizeof(char));
				sprintf(message, "query$%s\n", new_data);
				send_threaded_chat("bot", message);
				free(message);
			} else if (strcmp(servername, "PVPROUND") == 0) {
				message = (char *) malloc((strlen("PVPROUND$$") + strlen(server->name) + strlen(new_data) + 5)*sizeof(char));
				sprintf(message, "PVPROUND$%s$%s\n", server->name, new_data);
				send_threaded_chat("bot", message);
				free(message);
			} else if (strcmp(server->name, "bot") == 0){
				if (strcmp(servername, "PVP") == 0) {
					//Bot is sending chat to a PvP server through default chat
					separator_index = strchr(new_data, '$') - new_data;
					new_data[separator_index] = '\0';
					actual_server_name = (char *) malloc((strlen(new_data) + 2)*sizeof(char));
					strcpy(actual_server_name, new_data);
					force_name = (char *) malloc((strlen(new_data + separator_index + 1) + 3)*sizeof(char));
					strcpy(force_name, new_data + separator_index + 1);

					separator_index = strchr(force_name, '$') - force_name;
					force_name[separator_index] = '\0';
					message_to_send = (char *) malloc((strlen(force_name + separator_index + 1) + 3)*sizeof(char));
					strcpy(message_to_send, force_name + separator_index + 1);

					log_chat(actual_server_name, message_to_send);

					message = (char *) malloc((strlen("/silent-command if game.forces[''] then game.forces[''].print('') end") + (2 * strlen(force_name)) + strlen(message_to_send) + 10)*sizeof(char));
					sprintf(message, "/silent-command if game.forces['%s'] then game.forces['%s'].print('%s') end\n", force_name, force_name, message_to_send);
					send_threaded_chat(actual_server_name, message);
					free(actual_server_name);
					free(message);
					free(message_to_send);
					free(force_name);
				} else {
					//Bot is sending chat to a normal server through default chat
					log_chat(servername, new_data);
					message = (char *) malloc((strlen("/silent-command game.print('')") + strlen(new_data) + 5)*sizeof(char));
					sprintf(message, "/silent-command game.print('%s')\n", new_data);
					send_threaded_chat(servername, message);
					free(message);
				}
			}
			free(new_data);
		} else if (strstr(data, " [CHAT] ") != NULL && strstr(data, "[DISCORD]") == NULL) {
			//Server is sending chat through default chat, relay it to bot
			//Also includes check to prevent echoing
			new_data = (char *) malloc((strlen(strstr(data, " [CHAT] ") + strlen(" [CHAT] ")) + 4)*sizeof(char));
			strcpy(new_data, strstr(data, " [CHAT] ") + strlen(" [CHAT] "));
			log_chat(server->name, new_data);
			message = (char *) malloc((strlen(server->name) + strlen(new_data) + 6)*sizeof(char));
			sprintf(message, "%s$%s\n", server->name, new_data);
			send_threaded_chat("bot", message);
			free(message);
			free(new_data);
		} else if (strstr(data, " (shout):") != NULL && strstr(data, "[DISCORD]") == NULL) {
			if (data[4] == '-' && data[20] == ' ') {
				//Check for a timestamp, added in Factorio 0.15
				//Bug in earlier 0.15 versions had no space between timestamp and username
				//This finds the unbugged version
				log_chat(server->name, data + 20);
				message = (char *) malloc((strlen(server->name) + strlen(data + 20) + 6) * sizeof(char));
				sprintf(message, "%s$%s\n", server->name, data + 20);
				send_threaded_chat("bot", message);
				free(message);
			} else if (data[4] == '-') {
				//Check for a timestamp, added in Factorio 0.15
				//Bug in earlier 0.15 versions had no space between timestamp and username
				//This finds the bugged version
				log_chat(server->name, data + 19);
				message = (char *) malloc((strlen(server->name) + strlen(data + 19) + 6) * sizeof(char));
				sprintf(message, "%s$%s\n", server->name, data + 19);
				send_threaded_chat("bot", message);
				free(message);
			} else {
				//Factorio 0.14 or lower
				log_chat(server->name, data);
				message = (char *) malloc((strlen(server->name) + strlen(data) + 6) * sizeof(char));
				sprintf(message, "%s$%s\n", server->name, data);
				send_threaded_chat("bot", message);
				free(message);
			}
		}
	}
	//After server is closed, free memory and close file streams
	free(servername);
	free(data);
	if (strcmp(server->name, "bot") != 0) {
		//If Factorio server, close the logfile
		fclose(logfile);
	}
	fclose(input);

	return (void *) NULL;
}

//Contrary to what the name suggests, this function can launch either the bot or a server successfully
//This will return a struct containing the name of the server
//The struct also contains the file descriptors relating to the input and output of the server
char * launch_server(char * name, char ** args, char * logpath) {
	char *server_status = get_server_status(name);

	//Check to see if server is already running
	if (strcmp(server_status, "Server Running") == 0) return "Server Running";

	//Create copy of name, because of the weirdness of how C pointers works
	//Required to allow multiple servers
	char * name_copy = (char *) malloc((strlen(name) + 2)*sizeof(char));
	strcpy(name_copy, name);

	//Create logfile filepath, if this is not the bot
	char *logfile;
	if (strcmp(name_copy,"bot") != 0) {
		// "/var/www/factorio/name/screenlog.0"
		logfile = (char *) malloc((strlen(logpath) + strlen("/screenlog.0") + 2)*sizeof(char));
		strcpy(logfile, logpath);
		strcat(logfile, "/screenlog.0");
	} else {
		logfile = "bot";
	}

	//Create chatlog filepath, if this is not the bot
	char *chatlog;
	if (strcmp(name_copy,"bot") != 0) {
		// "/var/www/factorio/name/chatlog.0"
		chatlog = (char *) malloc((strlen(logpath) + strlen("/chatlog.0") + 2)*sizeof(char));
		strcpy(chatlog, logpath);
		strcat(chatlog, "/chatlog.0");
	} else {
		chatlog = "bot";
	}

	//Create pipes
	int in_pipe[2];
	int out_pipe[2];

	if (pipe(in_pipe) == -1 || pipe(out_pipe) == -1) {
		fprintf(stderr, "Failure to create pipes.");
		exit(1);
	}

	//Fork process
	int pid = fork();

	if (pid < 0) {
		fprintf(stderr, "Failure to fork process.");
		exit(1);
	} else if (pid == 0) {
		//Child Process (Server)
		dup2(in_pipe[0], STDIN_FILENO);
		close(in_pipe[0]);
		close(in_pipe[1]);
		dup2(out_pipe[1], STDOUT_FILENO);
		close(out_pipe[1]);
		close(out_pipe[0]);
		if (execvp(args[0], args) == -1) {
			int errsv = errno;
			fprintf(stderr, "Failure to launch server. Error Code: ");
			switch (errsv) {
				case E2BIG:
					fprintf(stderr, "E2BIG\n");
					break;
				case EACCES:
					fprintf(stderr, "EACCES\n");
					break;
				case EFAULT:
					fprintf(stderr, "EFAULT\n");
					break;
				case EINVAL:
					fprintf(stderr, "EINVAL\n");
					break;
				case EIO:
					fprintf(stderr, "EIO\n");
					break;
				case EISDIR:
					fprintf(stderr, "EISDIR\n");
					break;
				case ELIBBAD:
					fprintf(stderr, "ELIBBAD\n");
					break;
				case ELOOP:
					fprintf(stderr, "ELOOP\n");
					break;
				case EMFILE:
					fprintf(stderr, "EMFILE\n");
					break;
				case ENAMETOOLONG:
					fprintf(stderr, "ENAMETOOLONG\n");
					break;
				case ENFILE:
					fprintf(stderr, "ENFILE\n");
					break;
				case ENOENT:
					fprintf(stderr, "ENOENT\n");
					break;
				case ENOEXEC:
					fprintf(stderr, "ENOEXEC\n");
					break;
				case ENOMEM:
					fprintf(stderr, "ENOMEM\n");
					break;
				case ENOTDIR:
					fprintf(stderr, "ENOTDIR\n");
					break;
				case EPERM:
					fprintf(stderr, "EPERM\n");
					break;
				case ETXTBSY:
					fprintf(stderr, "ETXTBSY\n");
					break;
				default:
					fprintf(stderr, "UNKNOWN - %d\n", errsv);
					break;
			}
			exit(1);
		}
	}
	//Only parent process reaches this point
	//Closes unneeded pipe ends, adds server to server_list, and creates new thread for monitoring
	close(in_pipe[0]);
	close(out_pipe[1]);
	if (strcmp(server_status, "Server Does Not Exist") == 0) {
		server_list = (struct ServerData **) realloc(server_list, ((servers + 1) * sizeof(struct ServerData *)));
		struct ServerData *server = (struct ServerData *) malloc(sizeof(struct ServerData));
		server->serverid = servers;
		server->pid = pid;
		server->name = name_copy;
		server->input = in_pipe[1];
		server->output = out_pipe[0];
		pthread_mutex_t mymutex = PTHREAD_MUTEX_INITIALIZER;
		server->mutex = mymutex;
		server->status = "Started";
		server->logfile = logfile;
		server->chatlog = chatlog;
		pthread_mutex_t mymutex2 = PTHREAD_MUTEX_INITIALIZER;
		server->chat_mutex = mymutex2;
		server_list[servers] = server;
		thread_list = (pthread_t *) realloc(thread_list, ((servers + 1) * sizeof(pthread_t)));
		pthread_create(&thread_list[servers], &thread_attr, input_monitoring, (void *) server_list[servers]);
		servers++;

		return "New Server Started";
	} else {
		free(name_copy);
		struct ServerData *server = find_server(name);
		server->pid = pid;
		server->input = in_pipe[1];
		server->output = out_pipe[0];
		server->logfile = logfile;
		server->chatlog = chatlog;
		if (strcmp(server->status, "Restarting") != 0) pthread_create(&thread_list[server->serverid], &thread_attr, input_monitoring, (void *) server_list[server->serverid]);
		else pthread_create(&thread_list[server->serverid], &thread_attr, bot_ready_watch, (void *) server_list[server->serverid]);
		server->status = "Started";

		return "Old Server Restarted";
	}
}

//Start a server
char * start_server(char * name, char * input) {
	char *token;
	char *delim = ",\n\t";
	char **args = (char **) malloc(6*sizeof(char *));
	char **launchargs = (char **) malloc(10*sizeof(char *));
	int i = 0;
	int j = 0;

	token = strtok(input, delim);
	args[i++] = token;
	while (token != NULL) {
		token = strtok(NULL, delim);
		args[i++] = token;
	}

	i = 0;

	//Process of setting up the arguments for the execvp() call
	launchargs[i++] = "TEMP";
	if (strcmp(args[j++],"true") == 0) {
		launchargs[i++] = "--start-server-load-latest";
	} else {
		launchargs[i++] = "--start-server";
		launchargs[i++] = args[j++];
	}
	launchargs[i++] = "--port";
	launchargs[i++] = args[j++];
	launchargs[i++] = "-c";
	launchargs[i] = (char *) malloc((strlen(args[j]) + strlen("/config/config.ini") + 1)*sizeof(char));
	strcpy(launchargs[i], args[j]);
	strcat(launchargs[i], "/config/config.ini");
	i++;
	launchargs[i++] = "--server-setting";
	launchargs[i] = (char *) malloc((strlen(args[j]) + strlen("/server-settings.json") + 1)*sizeof(char));
	strcpy(launchargs[i], args[j]);
	strcat(launchargs[i], "/server-settings.json\0");
	i++;
	launchargs[i] = (char *) NULL;
	launchargs[0] = args[j + 1];

	char * result = launch_server(name, launchargs, args[j]);

	free(launchargs[i-1]);
	free(launchargs[i-3]);
	free(launchargs);
	free(args);

	return result;
}

//Stop a currently running server
char * stop_server(char * name) {
	//If server is not running
	if (strcmp(get_server_status(name), "Server Stopped") == 0) return "Server Not Running";
	if (strcmp(get_server_status(name), "Server Does Not Exist") == 0) return "Server Not Running";

	//Get the server to shut down
	struct ServerData *server = find_server(name);

	int endID;
	int successful = 0;
	kill(server->pid, SIGINT); //Send CTRL-C to the server, should close pipes on server end
	for (int i = 0; i < 10; i++) {
		//Wait for 10 seconds to see if server closed successfully
		endID = waitpid(server->pid, NULL, WNOHANG); //Check if server is closed
		if (endID == server->pid) {
			successful = 1;
			break;
		}
		sleep(1);
	}
	if (successful == 0) {
		//If server did not close normally, force close the server
		kill(server->pid, SIGKILL);
		if (strcmp(name, "bot") != 0) {
			FILE *logfile = fopen(server->logfile, "a");
			fputs("The server took too long to shut down and had to be force closed. Data loss may have occured.\r\n", logfile);
			fflush(logfile);
			fclose(logfile);
		}
	}
	pthread_join(thread_list[server->serverid], NULL); //Wait for thread to terminate
	close(server->input); //Close input pipe
	close(server->output); //Close output pipe
	if (strcmp(server->name, "bot") != 0) free(server->logfile); //Free memory allocated for logfile
	if (strcmp(server->name, "bot") != 0) free(server->chatlog); //Free memory allocated for chatlog
	server->status = "Stopped";

	return "Server Stopped";

}

void stop_all_servers() {
	for (int i = 1; i < servers; i++) {
		stop_server(server_list[i]->name);
		fprintf(stdout, "Server %s Shutdown\n", server_list[i]->name);
		char *announcement = malloc((strlen(server_list[i]->name) + strlen("$**[ANNOUNCEMENT]** Server has stopped!") + 1)*sizeof(char));
		strcpy(announcement, server_list[i]->name);
		strcat(announcement, "$**[ANNOUNCEMENT]** Server has stopped!");
		send_threaded_chat("bot", announcement);
		free(announcement);
	}
	//Shut down the bot
	sleep(1);
	struct ServerData *bot = server_list[0];
	kill(bot->pid, SIGINT);
	waitpid(bot->pid, NULL, 0);
	pthread_join(thread_list[0], NULL);
	close(bot->input); //Close input pipe
	close(bot->output); //Close output pipe
	//Exit successfully
	exit(0);
}

void * bot_ready_watch(void * vbot) {
	struct  ServerData *bot = (struct ServerData *) vbot;
	FILE *input = fdopen(dup(bot->output), "r");
	char *data = (char *) malloc(2001*sizeof(char));
	while (1) {
		fgets(data, 2001, input);
		if (strcmp(data, "ready$\n") == 0) break;
	}
	bot_ready = 1;
	fclose(input);
	free(data);
	return (void *) NULL;
}

void launch_bot() {
	char **botargs = (char **) malloc(3*sizeof(char *));
	botargs[0] = "node\0";
	botargs[1] = "./3RaFactorioBot.js\0";
	botargs[2] = (char *) NULL;
	launch_server("bot", botargs, "bot");
	free(botargs);

	while (bot_ready == 0) {
		//Wait for the bot to reply that it's ready.
		sleep(1);
	}
}

void server_crashed(struct ServerData * server) {
	//The server has crashed
	close(server->input); //Close input pipe
	close(server->output); //Close output pipe
	if (strcmp(server->name, "bot") != 0) free(server->logfile); //Free memory allocated for logfile
	if (strcmp(server->name, "bot") != 0) free(server->chatlog); //Free memory allocated for chatlog
	server->status = "Stopped";

	if (strcmp(server->name, "bot") == 0) {
		bot_ready = 0;
		launch_bot();
		//Set up the timestamp
		//YYYY-MM-DD HH:MM:SS
		time_t current_time = time(NULL);
		struct tm *time_data = localtime(&current_time);
		char *timestamp = (char *) malloc((strlen("YYYY-MM-DD HH:MM:SS") + 3) * sizeof(char));
		sprintf(timestamp, "%04d-%02d-%02d %02d:%02d:%02d", time_data->tm_year + 1900, time_data->tm_mon + 1, time_data->tm_mday, time_data->tm_hour, time_data->tm_min, time_data->tm_sec);
		char *output_message = (char *) malloc((strlen(timestamp) + strlen("emergency$") + 4)*sizeof(char));
		sprintf(output_message, "emergency$%s\n", timestamp);
		free(timestamp);
		FILE *output = fdopen(dup(server->input), "a");
		fputs(output_message, output);
		if (fclose(output) == EOF && errno == EPIPE) {
			fprintf(stderr, "The bot crashed and was unable to be restarted.");
			exit(1);
			return;
		}
		free(output_message);
	} else {
		char *output_message = malloc((strlen("crashreport$") + strlen(server->name) + 5)*sizeof(char));
		sprintf(output_message, "crashreport$%s\n", server->name);
		send_threaded_chat("bot", output_message);
		free(output_message);
		currently_running--;
		if (currently_running == 0) {
			//Shut down the bot, giving it time to finish whatever action it is doing
			sleep(5);
			struct ServerData *bot = server_list[0];
			kill(bot->pid, SIGINT);
			waitpid(bot->pid, NULL, 0);
			pthread_join(thread_list[0], NULL);
			close(bot->input); //Close input pipe
			close(bot->output); //Close output pipe
			//Free allocated memory
			free(server_list);
			free(thread_list);
			//Exit with error
			exit(1);
		}
	}

	pthread_mutex_unlock(&server->mutex);
}

void * heartbeat() {
	//Sends a heartbeat to every running server. This heartbeat doesn't actually do anything except force C to use the pipe, which triggers crash detection
	while (1) {
		send_threaded_chat("bot", "heartbeat$");
		for (int i = 1; i < servers; i++) {
			send_threaded_chat(server_list[i]->name, "/silent-command local heartbeat = true\n");
		}
		sleep(15);
	}

	return (void *) NULL;
}

int main() {
	//Initial setup of variables
	servers = 0;
	char *input = (char *) malloc(1000*sizeof(char));
	char *servername = (char *) malloc(sizeof(char)); //Allocate a char * array of size 1 to prevent bugs later
	server_list = (struct ServerData **) malloc(sizeof(struct ServerData *));
	thread_list = (pthread_t *) malloc(sizeof(pthread_t));
	int separator_index;
	currently_running = 0;
	bot_ready = 0;
	pthread_attr_init(&thread_attr);
	pthread_attr_setdetachstate(&thread_attr, PTHREAD_CREATE_JOINABLE);

	//Redirect certain signals to perform other functions
	if (signal(SIGINT, stop_all_servers) == SIG_ERR) fprintf(stderr, "Failure to ignore interrupt signal.\n"); //Safe shutdown of all servers
	if (signal(SIGPIPE, SIG_IGN) == SIG_ERR) fprintf(stderr, "Failure to ignore broken pipe signal.\n"); //Crash detection

	//Launch the bot
	launch_bot();

	//Create the heartbeat, also for improved crash detection
	pthread_t heartbeat_thread;
	pthread_create(&heartbeat_thread, &thread_attr, heartbeat, (void *) NULL);

	//Declare variables used in input parsing
	char *new_input;
	char *server_args;
	char *announcement;
	char *message;

	//Input scan loop
	while (1) {
		if (fgets(input, 1000, stdin) == NULL || input[0] == '\n') {
			fprintf(stderr, "Failure to receive input");
			exit(1);
		}

		//Gets the server identifier
		separator_index = strchr(input,'$') - input;
		input[separator_index] = '\0';
		servername = (char *) realloc(servername, (separator_index + 2)*sizeof(char));
		strcpy(servername, input);
		new_input = (char *) malloc((strlen(input + separator_index + 1) + 2)*sizeof(char));
		strcpy(new_input, input + separator_index + 1);
		if (strchr(new_input,'\n') != NULL) new_input[strchr(new_input,'\n') - new_input] = '\0';

		//Checks for command
		if (strchr(new_input,'$') != NULL) {
			//Start command
			separator_index = strchr(new_input,'$') - new_input;
			new_input[separator_index] = '\0';
			server_args = (char *) malloc((strlen(new_input + separator_index + 1) + 2)*sizeof(char));
			strcpy(server_args, new_input + separator_index + 1);
			if (strcmp(start_server(servername, server_args), "Server Running") == 0) {
				fprintf(stdout, "Server %s Already Running\n", servername);
				free(server_args);
				free(new_input);
				continue;
			}
			fprintf(stdout, "Server %s Started\n", servername);
			announcement = malloc((strlen(servername) + strlen("$**[ANNOUNCEMENT]** Server has started!\n") + 3)*sizeof(char));
			strcpy(announcement, servername);
			strcat(announcement, "$**[ANNOUNCEMENT]** Server has started!\n");
			send_threaded_chat("bot", announcement);
			free(announcement);
			free(server_args);
			currently_running++;
		} else if (strcmp(new_input, "stop") == 0) {
			//Stop command
			if (strcmp(stop_server(servername), "Server Not Running") == 0) {
				fprintf(stdout, "Server %s Not Running\n", servername);
				continue;
			}
			fprintf(stdout, "Server %s Stopped\n", servername);
			currently_running--;
			announcement = malloc((strlen(servername) + strlen("$**[ANNOUNCEMENT]** Server has stopped!\n") + 3)*sizeof(char));
			strcpy(announcement, servername);
			strcat(announcement, "$**[ANNOUNCEMENT]** Server has stopped!\n");
			send_threaded_chat("bot", announcement);
			free(announcement);
			if (currently_running == 0) break;
		} else if (strcmp(new_input, "status") == 0) {
			//Status command
			fprintf(stdout, "%s\n", get_server_status(servername));
		} else if (strcmp(new_input, "force_close") == 0) {
			//Force close a server
			//If server is not running
			if (strcmp(get_server_status(servername), "Server Stopped") == 0) continue;
			if (strcmp(get_server_status(servername), "Server Does Not Exist") == 0) continue;

			//Get the server to shut down
			struct ServerData *server = find_server(servername);

			kill(server->pid, SIGKILL); //Send SIGKILL to the server, forcing an immediate shutdown

			fprintf(stdout, "Server %s Stopped\n", servername);
			currently_running--;
			announcement = malloc((strlen(servername) + strlen("$**[ANNOUNCEMENT]** Server has stopped!\n") + 3)*sizeof(char));
			strcpy(announcement, servername);
			strcat(announcement, "$**[ANNOUNCEMENT]** Server has stopped!\n");
			send_threaded_chat("bot", announcement);
			free(announcement);
			if (currently_running == 0) break;
		} else {
			//Chat or in-game command
			message = (char *) malloc((strlen(new_input) + 4)*sizeof(char));
			strcpy(message, new_input);
			strcat(message, "\n");
			send_threaded_chat(servername, message);
			if (strstr(message, "[WEB]") != NULL) log_chat(servername, strstr(message, "[WEB]"));
			free(message);
		}
		free(new_input);
	}
	//Shut down the bot, giving it time to finish whatever action it is doing
	sleep(5);
	struct ServerData *bot = server_list[0];
	kill(bot->pid, SIGINT);
	waitpid(bot->pid, NULL, 0);
	pthread_join(thread_list[0], NULL);
	close(bot->input); //Close input pipe
	close(bot->output); //Close output pipe
	//Free allocated memory
	free(input);
	free(servername);
	free(server_list);
	free(thread_list);
	//Exit successfully
	exit(0);
}

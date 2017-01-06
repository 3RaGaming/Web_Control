#!/bin/bash
dir_base="$( dirname "${BASH_SOURCE[0]}" )";
datetime=$(date +%F-%T)
cd "$dir_base";
#put all recieved arguments into an $args[index] array
args=("$@");

#used to clean certain variables
function sanitize() {
	# first, strip underscores
	local work="$1"
	work=${work//_/}
	# next, replace spaces with underscores
	work=${work// /_}
	# now, clean out anything that's not alphanumeric or an underscore
	work=${work//[^a-zA-Z0-9_]/}
	# finally, lowercase with TR
	clean=`echo -n $work | tr A-Z a-z`
}

#used to move server folder log files
function move_logs() {
    local work="$1"
    if [ ! -d "$1/logs" ]; then
        mkdir -p "$1/logs"
    fi
    #Work in a screenlog archive here
	if [ -s "$1/screenlog.0" ]; then
		mv "$1/screenlog.0" "$1/logs/screenlog-${datetime}.0"
	fi
    #Work in a factorio-current archive here
	if [ -s "$1/factorio-current.log" ]; then
		mv "$1/factorio-current.log" "$1/logs/factorio-current-${datetime}.log"
	fi
    
}

#global way to get status of server.
function get_status() {
	local work="$1"
	firstcheck=$(sudo -u www-data screen -ls | grep manage | awk '$1=$1');
	if [ "$firstcheck" ]; then
		sudo -u www-data screen -S manage -X at 0 stuff "${work}\\\$status\n"
		secondcheck=$(tail -1 screenlog.0);
        sanitize "$secondcheck"
		if [ "$clean" == "server_running" ]; then
			check="Server Running"
		else
			check="Server Stopped"
		fi
	else
		check="Manage Stopped"
	fi
}

for dir in `ls -d */ | sed 's|/||'`; do
	sanitize "$dir"
	if [ "$clean" == "${args[0]}" ]; then
		server="$clean";
	fi
done

if [ -z "$server" ]; then
	echo "Error in input";
else
	var_cont=true;
	################################
	#### Remove this when ready
	################################
	#server="factorio"
	dir_server="$dir_base/$server";
	#echo "$dir_server"
	#important files
	#config/config.ini
	if [ ! -e "$dir_server/config/config.ini" ]; then
		echo "Missing config.ini"; var_cont=false;
	else 
		port=$(echo "$dir_server" | grep -o -E '[0-9]+')
		if [ -z "$port" ]; then
			port="0";
		fi
                port="3429$port"
	fi
	#server_settings.ini
	if [ ! -e "$dir_server/server-settings.json" ]; then echo "Missing server-settings.json"; var_cont=false; fi
	#player_data.json
	if [ ! -e "$dir_server/player-data.json" ]; then echo "Missing player-data.json"; var_cont=false; fi
	#banlist.json
	if [ ! -e "$dir_server/banlist.json" ]; then echo "Missing banlist.json"; var_cont=false; fi
	if [ -z "$port" ]; then echo "Port is incorrectly configured in config.ini"; fi
	#saves/
	sanitize "${args[2]}"
	cur_user="$clean"
	sanitize "${args[1]}"
	#cd $dir_server #This may need to be changed to the location of managepgm, not sure
	case "$clean" in
	    'prestart')
			get_status "$server"
			if [ "$check" == "Server Running" ]; then 
				#echo -e "${check}"
				echo "running" ;
			else
				echo "stopped";
			fi
            ;;
        'start')
			get_status "$server"
			if [ "$check" == "Server Running" ]; then 
				echo -e "Attempted Start by $cur_user: Server is already running\r\n" >> $dir_server/screenlog.0 ;
			elif [ "$check" == "Manage Stopped" ]; then
				#Work in a screenlog archive here
				if [ -s "screenlog.0" ]; then
					mkdir -p logs
					mv screenlog.0 logs/screenlog.0-${datetime}
				fi
				sudo -u www-data /usr/bin/screen -d -m -L -S manage ./managepgm
				sudo -u www-data /usr/bin/screen -r manage -X colon "log on^M"
				sudo -u www-data /usr/bin/screen -r manage -X colon "logfile filename screenlog.0^M"
				sudo -u www-data /usr/bin/screen -r manage -X colon "logfile flush 0^M"
				sudo -u www-data /usr/bin/screen -r manage -X colon "multiuser on^M"
				sudo -u www-data /usr/bin/screen -r manage -X colon "acladd root^M"
				sudo -u www-data /usr/bin/screen -r manage -X colon "acladd user^M"
				if [ "${args[3]}" ]; then
				    sanitize "${args[3]}";
				    #only set $server_file if the file appears to be valid.
				    #$server_file="$clean";
				fi

				#Load server_file if it's set. Or else just load latest
                move_logs "$server"
				if [ "$server_file" ]; then
					echo -e "Starting Server. ${server_file}. Initiated by $cur_user\r\n" >> $dir_server/screenlog.0 ;
					#sudo -u www-data screen -S manage -X at 0 stuff "${server}\\\$start\\\$true,${port},${dir_server}\n"
				else
					echo -e "Starting Server. Load Latest. Initiated by $cur_user\r\n" >> $dir_server/screenlog.0 ;
					sudo -u www-data screen -S manage -X at 0 stuff "${server}\\\$start\\\$true,${port},${dir_server}\n"
				fi
			else
				if [ "$var_cont" == false ] ; then
					echo "Cannot start server";
				else
					echo -e "Starting Server. Initiated by $cur_user\r\n" >> $dir_server/screenlog.0 ;
					if [ -e "$dir_server/screenlog.0" ]; then
						LASTDATA=$(tail -n 50 $dir_server/screenlog.0)
                        move_logs "$server"
						echo "${LASTDATA}" > $dir_server/screenlog.0 ;
					fi
					sudo -u www-data screen -S manage -X at 0 stuff "${server}\\\$start\\\$true,${port},${dir_server}\n"	
				fi
			fi
            ;;
         
        'stop')
			get_status "$server"
			if [ "$check" == "Server Running" ]; then 
				#echo "Server Shutting Down" ;
				echo -e "Server Shutting Down. Initiated by $cur_user\r\n" >> screenlog.0 ;
				sudo -u www-data screen -S manage -X at 0 stuff "${server}\\\$stop\n"
			else
				echo "Server is already Stopped.";
			fi
            ;;

        'status')
			get_status "$server"
			if [ "$check" == "Server Running" ]; then 
				#echo -e "${check}"
				echo "Server is Running" ;
			else
				echo "Server is Stopped";
			fi
            ;;

        *)
            echo $"Usage: $0 server_select {start|stop|status} user"
            exit 1
	esac
fi

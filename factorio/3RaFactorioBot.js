//Set up the Discord bot interface
var Discord = require("discord.js");
var bot = new Discord.Client();

//Set up the channel list
var fs = require("fs");
var channels;
try {
    channels = JSON.parse(fs.readFileSync("./channel_list.json", "utf8"));
} catch (err) {
    if (err.code == "ENOENT") {
        fs.writeFileSync("channel_list.json", JSON.stringify({}));
        channels = {};
    }
}

//Set up a blank list for PvP Player storage, does not need to persist through restarts
pvplists = {};

//Create the command list
//Perhaps export this to a JSON file like the channel list is?
var commandlist = {
    "setserver": function (message, command) {
        if (command.length < 3) {
            message.channel.sendMessage("The setserver command requires 2 arguments. ::setserver serverid servername");
            return;
        }
        //Check to see if serverid is already registered
        let serverid = command[1];
        if (channels[serverid]) {
            message.channel.sendMessage("Server " + serverid + " is already registered to another Discord channel! Please go ::unset the original first.\n");
            return;
        }
        //Check to see if this channel is already registered
        let current = getChannelKey(channels, message.channel.id);
        if (current !== null) {
            message.channel.sendMessage("This channel is already registered! Please use ::unset first if you want to change this.\n");
            return;
        }
        //Get the name to tag the server as
        let servername = command.slice(2).join(" ");
        channels[serverid] = { id: message.channel.id, name: servername, type: "server" };
        fs.unlinkSync("channel_list.json");
        fs.writeFileSync("channel_list.json", JSON.stringify(channels));
        message.channel.sendMessage("Messages from server " + serverid + " will now be sent to this channel with the prefix [" + servername + "].\n");
    },
    "setchannel": function (message, command) {
        if (command.length < 3) {
            message.channel.sendMessage("The setchannel command requires 2 arguments. ::setchannel channelid channelname");
            return;
        }
        //Check to see if channelid is already registered
        let channelid = command[1];
        if (channels[channelid]) {
            message.channel.sendMessage("Channel " + channelid + " is already registered to another Discord channel! Please go ::unset the original first.\n");
            return;
        }
        //Check to see if this channel is already registered
        let current = getChannelKey(channels, message.channel.id);
        if (current !== null) {
            message.channel.sendMessage("This channel is already registered! Please use ::unset first if you want to change this.\n");
            return;
        }
        //Get the name to tag the server as
        let channelname = command.slice(2).join(" ");
        channels[channelid] = { id: message.channel.id, name: channelname, type: "chat" };
        fs.unlinkSync("channel_list.json");
        fs.writeFileSync("channel_list.json", JSON.stringify(channels));
        message.channel.sendMessage("Messages from channel " + channelid + " will now be sent to this channel with the prefix [" + channelname + "].\n");
    },
    "setpvp": function (message, command) {
        if (command.length < 4) {
            message.channel.sendMessage("The setpvp command requires 3 arguments. ::setchannel serverid forcename servername");
            return;
        }
        //Check to see if pvpid is already registered
        let serverid = command[1];
        let forcename = command[2];
        let pvpid = serverid + "-" + forcename;
        if (channels[pvpid]) {
            message.channel.sendMessage("This force is already registered to another Discord channel! Please go ::unset the original first.");
            return;
        }
        //Check to see if this channel is already registered
        let current = getChannelKey(channels, message.channel.id);
        if (current !== null) {
            message.channel.sendMessage("This channel is already registered! Please use ::unset first if you want to change this.\n");
            return;
        }
        //Get the name to tag the server as
        let servername = command.slice(3).join(" ");
        channels[pvpid] = { id: message.channel.id, name: servername + "-" + forcename, type: "pvp" };
        message.channel.sendMessage("Messages from force " + forcename + " on server " + serverid + " will now be sent to this channel with the prefix [" + servername + "-" + forcename + "].\n");
        if (!channels[serverid]) channels[serverid] = { id: null, name: servername, type: "pvp-main", forces: [pvpid] };
        else if (channels[serverid].type == "registered") channels[serverid] = { id: null, name: servername, type: "pvp-main", forces: [pvpid] };
        else channels[serverid].forces.push(pvpid);
        if (!pvplists[serverid]) pvplists[serverid] = {};
        fs.unlinkSync("channel_list.json");
        fs.writeFileSync("channel_list.json", JSON.stringify(channels));
    },
    "unset": function (message, command) {
        //Check to see if the server is registered to a channel
        let remove = getChannelKey(channels, message.channel.id);
        if (remove === null) {
            message.channel.sendMessage("There is nothing registered to this channel");
            return;
        }
        if (channels[remove].type == "pvp") {
            let main_name = sendto.substring(0, sendto.indexOf("-"));
            let main_channel = channels[main_name];
            main_channel.forces.splice(main_channel.forces.indexOf(remove), 1);
            if (main_channel.forces.length === 0) delete channels[main_name];
        }
        //Delete the server registration and update the channel_list.json
        delete channels[remove];
        fs.unlinkSync("channel_list.json");
        fs.writeFileSync("channel_list.json", JSON.stringify(channels));
        message.channel.sendMessage("Successfully unregistered.\n");
    },
    "setadmin": function (message, command) {
        //Set the admin warning messages to be delivered to this current channel
        let current = getChannelKey(channels, message.channel.id)
        if (current !== null) message.channel.sendMessage("The admin channel is currently already set. This command will overwrite the previous admin channel.\n");
        channels["admin"] = { id: message.channel.id, name: "Admin", type: "admin" };
        fs.unlinkSync("channel_list.json");
        fs.writeFileSync("channel_list.json", JSON.stringify(channels));
        message.channel.sendMessage("All Admin warnings and messages will now be sent here.\n");
    },
    "sendadmin": function (message, command) {
        if (channels["admin"]) {
            if (channels["admin"].id == message.channel.id) {
                if (command.length < 3) {
                    message.channel.sendMessage("Correct usage: ::sendadmin [serverid/all] command");
                    return
                } 
                let server = command[1];
                if (channels[server] || server == "all") {
                    let sendcommand = command.slice(2).join(" ");
                    let sendstring = "admin$" + server + "$/silent-command " + sendcommand.replace(/\n/g, " ") + "\n";
                    safeWrite(sendstring);
                } else {
                    message.channel.sendMessage("Serverid is not a registered server or 'all'.");
                }
                return;
            }
        }
        message.channel.sendMessage("Admin commands can only be done from the registered admin channel. Use ::setadmin to register one if you haven't already.");
    },
    "adminannounce": function (message, command) {
        if (channels["admin"]) {
            if (channels["admin"].id == message.channel.id) {
                if (command.length < 3) {
                    message.channel.sendMessage("Correct usage: ::adminannounce [serverid/all] announcement");
                    return
                }
                let server = command[1];
                if (channels[server] || server == "all") {
                    let announcement = command.slice(2).join(" ");
                    let sendstring = "admin$" + server + "$/silent-command game.print('[ANNOUNCEMENT] " + clean_message(announcement) + "')" + "\n";
                    safeWrite(sendstring);
                } else {
                    message.channel.sendMessage("Serverid is not a registered server or 'all'.");
                }
                return;
            }
        }
        message.channel.sendMessage("Admin commands can only be done from the registered admin channel. Use ::setadmin to register one if you haven't already.");
    },
    "registerserver": function (message, command) {
        if (channels["admin"]) {
            if (channels["admin"].id == message.channel.id) {
                if (command.length != 2) {
                    message.channel.sendMessage("Correct usage: ::registerserver serverid");
                    return
                }
                let serverid = command[1];
                if (channels[serverid]) {
                    message.channel.sendMessage("This server is already registered!");
                } else {
                    channels[serverid] = { id: null, name: null, type: "registered" };
                    message.channel.sendMessage("Server " + serverid + " has been registered.");
                    fs.unlinkSync("channel_list.json");
                    fs.writeFileSync("channel_list.json", JSON.stringify(channels));
                }
                return;
            }
        }
        message.channel.sendMessage("Admin commands can only be done from the registered admin channel. Use ::setadmin to register one if you haven't already.");
    },
    "unregister": function (message, command) {
        if (channels["admin"]) {
            if (channels["admin"].id == message.channel.id) {
                if (command.length != 2) {
                    message.channel.sendMessage("Correct usage: ::unregister serverid");
                    return
                }
                if (!channels[serverid]) {
                    message.channel.sendMessage("This server is not registered!");
                } else {
                    if (channels[serverid].type != "registered") {
                        message.channel.sendMessage("This server was not registered with ::registerserver. This command will not work for this server.");
                    } else {
                        delete channels[serverid];
                        message.channel.sendMessage("Server " + serverid + " has been unregistered.");
                        fs.unlinkSync("channel_list.json");
                        fs.writeFileSync("channel_list.json", JSON.stringify(channels));
                    }
                }
                return;
            }
        }
        message.channel.sendMessage("Admin commands can only be done from the registered admin channel. Use ::setadmin to register one if you haven't already.");
    },
    "banhammer": function (message, command) {
        if (channels["admin"]) {
            if (channels["admin"].id == message.channel.id) {
                if (command.length != 2) {
                    message.channel.sendMessage("Correct usage: ::banhammer Factorio_username");
                    return
                }
                let username = command[1];
                let sendstring = "admin$all$/ban " + username + " 'Speak to us at www.3ragaming.com/Discord to request an appeal'\n";
                safeWrite(sendstring);
                message.channel.sendMessage("Player " + username + " has been banned from all currently running 3Ra servers.\n");
                return;
            }
        }
        message.channel.sendMessage("Admin commands can only be done from the registered admin channel. Use ::setadmin to register one if you haven't already.");
    },
    "help": function (message, command) {
        message.channel.sendMessage("**::setserver** *serverid servername* - Any messages internally tagged with serverid will be sent to the channel this command is run in, prefixed with '[servername]'\n\n" +
            "**::setchannel** *channelid channelname* - Same as above, but using chat channels (coded by Articulating) rather than servers\n\n" +
            "**::setpvp** *serverid forcename servername* - Only the messages from a specific force (forcename) of a PvP server will be sent to this channel (other arguments same as above)\n\n" +
            "**::unset** - Unsets a channel that was previously registered using ::setserver, ::setchannel, or ::setpvp\n\n" +
            "**::setadmin** - Sets the channel that all admin warnings and messages are to be delivered to. " +
            "All commands following this command are admin commads and must be run in the admin channel that this command registers.\n\n" + 
            "**::sendadmin** *[serverid/all] command* - Sends 'command' to 'serverid' as if you were typing directly into the console (/silent-command will automatically be attached to the beginning). " +
            "Replace serverid with \"all\" to send to all running servers. Serverid must be registered.\n\n" +
            "**::adminannounce** *[serverid/all] announcement* - Sends an announcement to 'serverid'. Replace serverid with \"all\" to send to all running servers. Serverid must be registered\n\n" +
            "**::registerserver** *serverid* - Register a server for use, but do not attach a Discord channel to it. (Allows ::sendadmin and ::adminanounce to work)\n\n" +
            "**::unregister** *serverid* - Unregister a server registered with ::registerserver.\n\n" +
            "**::banhammer** *Factorio_username* - Bans a player from all running servers at once");
    }
};

//Cleans a message by escaping single quotes and double quotes, as well as clearing newlines
//Single quotes are double escaped, as the C program will strip one and the Factorio server will strip the other
function clean_message(message) {
    let escape_chars = message.replace(/\\/g, "");
    let single_quotes = escape_chars.replace(/'/g, "\\'");
    let new_lines = single_quotes.replace(/\n/g, " ");
    return new_lines;
}

//Function to get the key(s) relating to a value
function getChannelKey(object, value) {
    for (var key in object) {
        if (object[key].id == value) return key;
    }
    return null;
}

//Function to safely handle writing to stdout
function safeWrite(sendstring) {
    if (!process.stdout.write(sendstring)) {
        safe = false;
        process.stdout.once('drain', safeWrite(sendstring));
    } else {
        safe = true;
    }
}
var safe = true;

//Set utf8 encoding for both stdin and stdout
process.stdin.setEncoding('utf8');
process.stdout.setDefaultEncoding('utf8');

//Receive input from management program
process.stdin.on('readable', () => {
    let input = process.stdin.read();

    if (input !== null) {
        //Removes various invisible characters that would mess with the program
        if (input.indexOf("\r\n") != -1) input = input.substring(0, input.length - 2); //For testing on Windows, removes the \r\n that Windows adds with the Enter key
        if (input.indexOf("\n") != -1) input = input.substring(0, input.length - 1); //For testing on Linux, removes the \n that Linux adds with the Enter key

        //Get the channelid
        let separator = input.indexOf("$");
        let channelid = input.substring(0, separator);
        if (channelid == "admin") {
            //Admin Warning System
            let roleid = bot.guilds.get("143772809418637313").roles.find("name", "Moderators").id;
            let tag = "<@&" + roleid + ">";
            let new_input = input.substring(separator + 1);
            separator = new_input.indexOf("$");
            channelid = new_input.substring(0, separator);
            let channelname = channels[channelid].name;
            let message = new_input.substring(separator + 1);
            bot.channels.get(channels["admin"].id).sendMessage(
                tag + "\n" +
                "**Admin Warning System was set off!**\n" +
                "Server ID: " + channelid + "\n" +
                "Server Name: " + channelname + "\n" +
                "Message: " + message
            );
        } else if (channelid == "PLAYER") {
            //Player Update
            let new_input = input.substring(separator + 1);
            separator = new_input.indexOf("$");
            channelid = new_input.substring(0, separator);
            if (channels[channelid]) {
                let data = new_input.substring(separator + 1).split(","); //Replaces the newline at the end while also splitting the arguments apart
                let action = data[0]; //Join,Leave,Force (Changed Force)
                let player_id = data[1]; //Not really relevant, but included in case it may be needed
                let player_name = data[2]; //Player's username
                let force_name = data[3]; //Name of player's force
                if (channels[channelid].type == "pvp-main") {
                    pvplists[channelid][player_name] = force_name; //A simple player_name: force_name dictionary
                    channelid = channelid + "-" + force_name;
                    if (!channels[channelid]) return;
                }
                var message;
                switch (action) {
                    case "join":
                        message = "[PLAYER JOIN] Player " + player_name + " has joined the server!";
                        break;
                    case "leave":
                        message = "[PLAYER LEAVE] Player " + player_name + " has left the server!";
                        break;
                    case "force":
                        message = "[PLAYER FORCE] Player " + player_name + " has joined force " + force_name + "!";
                        break;
                }
                bot.channels.get(channels[channelid].id).sendMessage("[" + channels[channelid].name + "] " + message);
            }
        } else if (channels[channelid]) {
            if (channels[channelid].type == "registered") {
                return;
            } else if (channels[channelid].type == "pvp-main") {
                let message = input.substring(separator + 1);
                if (message == "[ANNOUNCEMENT] Server has started!") {
                    //Open the channel for chat if the server is running
                    let mainserver = channelid;
                    let forces = channels[channelid].forces;
                    for (let i = 0; i < forces.length; i++) {
                        channelid = forces[i];
                        let open_server = bot.channels.get(channels[channelid].id).overwritePermissions(bot.guilds.get("143772809418637313").roles.get("143772809418637313"), { 'SEND_MESSAGES': true });
                        open_server.then(() => {
                            bot.channels.get(channels[channelid].id).sendMessage("[" + channels[mainserver].name + "] " + message);
                        });
                    }
                } else if (message == "[ANNOUNCEMENT] Server has stopped!") {
                    //Close the channel for chat if the server is stopped
                    let mainserver = channelid;
                    let forces = channels[channelid].forces;
                    for (let i = 0; i < forces.length; i++) {
                        channelid = forces[i];
                        let message_sent = bot.channels.get(channels[channelid].id).sendMessage("[" + channels[channelid].name + "] " + input.substring(separator + 1));
                        message_sent.then((message) => {
                            bot.channels.get(channels[channelid].id).overwritePermissions(bot.guilds.get("143772809418637313").roles.get("143772809418637313"), { 'SEND_MESSAGES': false });
                        });
                    }
                } else {
                    //Server is a PvP server, send to correct channel
                    separator = message.indexOf(":");
                    let username = message.substring(0, separator);
                    if (username.indexOf("[") != -1) username = username.substring(0, username.indexOf("[") - 1); //Remove any tag on the username
                    let force_name = pvplists[channelid][username];
                    let pvp_channelid = channelid + "-" + force_name;
                    if (channels[pvp_channelid]) {
                        bot.channels.get(channels[pvp_channelid].id).sendMessage("[" + channels[pvp_channelid].name + "] " + message);
                    }
                }
            } else {
                //Server is not PvP, send message normally
                if (input.substring(separator + 1) == "[ANNOUNCEMENT] Server has started!") {
                    //Open the channel for chat if the server is running
                    let open_server = bot.channels.get(channels[channelid].id).overwritePermissions(bot.guilds.get("143772809418637313").roles.get("143772809418637313"), { 'SEND_MESSAGES': true });
                    open_server.then(() => {
                        bot.channels.get(channels[channelid].id).sendMessage("[" + channels[channelid].name + "] " + input.substring(separator + 1));
                    });
                } else if (input.substring(separator + 1) == "[ANNOUNCEMENT] Server has stopped!") {
                    //Close the channel for chat if the server is stopped
                    let message_sent = bot.channels.get(channels[channelid].id).sendMessage("[" + channels[channelid].name + "] " + input.substring(separator + 1));
                    message_sent.then((message) => {
                        bot.channels.get(channels[channelid].id).overwritePermissions(bot.guilds.get("143772809418637313").roles.get("143772809418637313"), { 'SEND_MESSAGES': false });
                    });
                } else {
                    bot.channels.get(channels[channelid].id).sendMessage("[" + channels[channelid].name + "] " + input.substring(separator + 1));
                }
            }
        } else return;
    }
});

//Receive input from Discord
bot.on('message', (message) => {
    //Ignore own messages
    if (message.author == bot.user) return;
    //Set the prefix
    let prefix = "::";

    //If message is a command, run the correct command. Else, forward to the proper server (if channel is registered)
    if (message.content.startsWith(prefix)) {
        if (!message.member.roles.has(message.guild.roles.find("name", "Moderators").id)) return;
        let command = message.cleanContent.substring(2).split(" ");
        if (commandlist[command[0]]) commandlist[command[0]](message, command);
        else return;
    } else {
        //Get an array of servers that match this channel id. End function if array length is 0 (unreigstered channel)
        let sendto = getChannelKey(channels, message.channel.id);
        var name;
        if (message.member.nickname === null) name = message.author.username;
        else name = message.member.nickname;
        if (sendto === null) return;
        if (channels[sendto].type == "admin") return; //Ignore normal chat from admins, use other commands to communicate from admins
        while (!safe) {
            //Wait here until safe to continue, should not happen often
        }
        var addon;
        if (channels[sendto].type == "chat") addon = "chat$" + sendto; //Setup to send to a chat channel
        else if (channels[sendto].type == "server") addon = sendto; //Setup to send to a server
        else {
            //Setup to send to a PVP server
            let serverid = sendto.substring(0, sendto.indexOf("-"));
            let force_name = sendto.substring(sendto.indexOf("-") + 1);
            addon = "PVP$" + serverid + "$" + force_name;
        }
        let sendstring = clean_message(addon + "$[DISCORD] " + name + ": " + message.cleanContent) + "\n";
        safeWrite(sendstring);
    }
});

//Leaves any server that isn't 3Ra
bot.on('ready', () => {
    bot.user.setGame("3Ra - Factorio");
    bot.guilds.forEach((guildobj, guildid, collection) => {
        if (guildid != "143772809418637313") guildobj.leave();
    });
    //Set any currently existing PvP servers back up for fresh player lists
    for (var key in channels) {
        if (channels[key].type == "pvp-main") pvplists[key] = {};
    }
});

//If the bot joins a server that isn't 3Ra, immediately leave it
bot.on('guildCreate', (guild) => {
    if (guild.id != "143772809418637313") guild.leave();
});

//WARNING: THIS TOKEN IS NOT TO BE SHARED TO THE PUBLIC
var token = JSON.parse(fs.readFileSync("./token.json", "utf8"))
bot.login(token);


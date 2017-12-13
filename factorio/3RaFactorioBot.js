//Set up the Discord bot interface
var Discord = require("discord.js");
var bot = new Discord.Client({fetchAllMembers: true, disableEveryone: true});

//Set up code to get line number of Promise Rejections
process.on("unhandledRejection", (err) => {
	console.error("Uncaught Promise Error: \n" + err.stack);
});

//Import the file system registration
var fs = require("fs");

//Get the config, fail if unable to or if config is not set up
var config;
var failure = false;
try {
	config = JSON.parse(fs.readFileSync("./config.json", "utf8"));
} catch (err) {
	console.log("DEBUG$Critical failure! Config file was not able to load successfully!");
	process.exit(1);
}

//Check if any specific config settings are missing, using default values if so.
if (!config.token) {
	console.log("DEBUG$(Config Error) 'token' not found, using default value. (This one will cause the bot to crash)");
	config.token = "PUT_YOUR_BOT_TOKEN_HERE";
}
if (!config.guildid) {
	console.log("DEBUG$(Config Error) 'guildid' not found, using default value. (268610395088879616)");
	config.guildid = "268610395088879616"; //3Ra's Test Server
}
if (!config.smallmodrole) {
	console.log("DEBUG$(Config Error) 'smallmodrole' not found, using default value. (false)");
	config.smallmodrole = false;
}
if (!config.modrole) {
	console.log("DEBUG$(Config Error) 'modrole' not found, using default value. (Moderators)");
	config.modrole = "Moderators";
}
if (!config.adminrole) {
	console.log("DEBUG$(Config Error) 'adminrole' not found, using default value. (Admin)");
	config.adminrole = "Admin";
}
if (!config.gamemessage) {
	console.log("DEBUG$(Config Error) 'gamemessage' not found, using default value (Use ::help for commands)");
	config.gamemessage = "Use ::help for commands";
}
if (!config.banreason) {
	console.log("DEBUG$(Config Error) 'banreason' not found, using default value (Contact the server owner to appeal your ban)");
	config.banreason = "Contact the server owner to appeal your ban";
}
if (!config.update_descriptions && typeof config.update_descriptions == 'undefined') {
    console.log("DEBUG$(Config Error) 'update_descriptions' not found, using default value (false)");
    config.update_descriptions = false;
} else if (config.update_descriptions && config.update_descriptions == 'false') {
    config.update_descriptions = false;
} else if (config.update_descriptions) {
    config.update_descriptions = true;
}

if (config.token == "PUT_YOUR_BOT_TOKEN_HERE") {
	console.log("DEBUG$Critical failure! The config file was not set up properly!");
	process.exit(1);
}

//Pull the config values
var token = config.token;
var guildid = config.guildid;
var smallmodrole = config.smallmodrole;
var modrole = config.modrole;
var adminrole = config.adminrole;
var gamemessage = config.gamemessage;
var banreason = config.banreason;
var update_descriptions = config.update_descriptions;

//Load the persistent save data
var savedata;
try {
	savedata = JSON.parse(fs.readFileSync("./savedata.json", "utf8"));
} catch (err) {
	if (err.code == "ENOENT") {
		fs.writeFileSync("savedata.json", JSON.stringify({ channels: {}, playerlists: {}, registration: {} }));
		savedata = { channels: {}, playerlists: {}, registration: {} };
	}
}
if (!savedata) {
	savedata = {};
	console.log("DEBUG$Non-critical failure! Unknown bug caused savedata not to load. Please investigate.");
}
if (!savedata.channels) savedata.channels = {};
if (!savedata.playerlists) savedata.playerlists = {};
if (!savedata.registration) savedata.registration = {};

//Cleans a message by escaping single quotes, clearing backslashes, and replacing newlines with spaces
function clean_message(message) {
	let escape_chars = message.replace(/\\/g, "");
	let single_quotes = escape_chars.replace(/'/g, "\\'");
	let new_lines = single_quotes.replace(/\n/g, " ");
	return new_lines;
}

//Function to get the key relating to a value
function getChannelKey(channelid) {
	for (var internalid in savedata.channels) {
		if (savedata.channels[internalid].id == channelid) return internalid;
	}
	return null;
}

//Function to get the Discord Unique ID of a registered username
function getPlayerID(username) {
	for (var userid in savedata.registration) {
		if (savedata.registration[userid].toLowerCase() == username.toLowerCase()) return userid;
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

//Find the server a player is in
function findPlayer(username) {
	for (var server in savedata.playerlists) {
		for (var player in savedata.playerlists[server]) {
			if (player.toLowerCase() == username.toLowerCase()) return server;
		}
	}
	return null;
}

//Sets up an object to store queries, not necessary for this to persist through saves.
var queryResponse = {};

//A function to resolve a promise when given a certain UID
function resolveQuery(uid, response) {
	queryResponse[uid].resolve(response);
	clearTimeout(queryResponse[uid].reject);
	delete queryResponse[uid];
}

//Query a server for information, returns a Promise to await the response properly
function serverQuery(server, query) {
	var randomuid;
	var uid;
	while (true) {
		//Generate a random UID that is not in use
		randomuid = Math.floor((Math.random() * 9999) + 1000);
		if (randomuid >= 10000) randomuid = randomuid - 1000;
		if (randomuid <= 999) randomuid = randomuid + 1000;
		uid = randomuid.toString();
		if (!queryResponse[uid]) break;
	}
	let sendstring = "admin$" + server + "$/silent-command local s,e = pcall(loadstring('" + clean_message(query) + "')) e = e ~= nil and print('output$" + uid + "$ ' .. tostring(e))\n";
	safeWrite(sendstring);
	queryResponse[uid] = { resolve: null, reject: null };
	return new Promise((resolve, reject) => {
		queryResponse[uid].resolve = resolve;
		queryResponse[uid].reject = setTimeout(() => {
			delete queryResponse[uid];
			reject("No response from server");
		}, 5000);
	});
};

//Assign a player to a PvP Role
function assignRole(server, force, userid) {
	let user = bot.guilds.get(guildid).members.get(userid);
	if (!user) return; //Should not happen, except maybe in rare cases involving invisible members
	if (!savedata.channels[server + "-" + force]) return;
	let roleid = savedata.channels[server + "-" + force].role;
	if (roleid === null) {
		let created = bot.guilds.get(guildid).createRole({ name: server + "-" + force });
		created.then((role) => {
			savedata.channels[server + "-" + force].role = role.id;
			user.addRole(role);
			roleid = role.id;
			if (!user.roles.has(roleid)) user.addRole(bot.guilds.get(guildid).roles.get(roleid)); //Redundancy to make sure it's added
			fs.unlinkSync("savedata.json");
			fs.writeFileSync("savedata.json", JSON.stringify(savedata));
			if (savedata.channels.admin) {
				let roleid = bot.guilds.get(guildid).roles.find("name", modrole).id;
				let tag = "<@&" + roleid + ">";
				version_send(bot.guilds.get(guildid).channels.get(savedata.channels.admin.id), tag + ": The role for server *" + server + "*, force *" + force + "* was missing and has been recreated. Please manually correct the channel permissions.");  
			}
		});
	} else if (roleid !== null && !user.roles.has(roleid)) {
		user.addRole(roleid);
	}
}

//Remove a player from any Role in a PvP Server, if he has one
function removeRole(server, force, userid) {
	if (savedata.channels[server + "-" + force]) {
		let user = bot.guilds.get(guildid).members.get(userid);
		if (!user) return null;
		let roleid = savedata.channels[server + "-" + force].role;
		if (roleid !== null && user.roles.has(roleid)) return user.removeRole(roleid);
	}
	return null;
}

//Replace any mentions with an actual tag
function replaceMentions(message) {
	//Replace @modrole with a Discord ping
	let modroleid = bot.guilds.get(guildid).roles.find("name", modrole).id;
	let modtag = "<@&" + modroleid + ">";
	let modreg = new RegExp(modrole, "ig");
	let moderators = message.replace(modreg, modtag);
	//Replace @smallmodrole with a Discord ping
	let smallmods = moderators;
    if (smallmodrole) {
        let smallmodroleid = bot.guilds.get(guildid).roles.find("name", smallmodrole).id;
        let smallmodtag = "<@&" + smallmodroleid + ">";
        let smallmodreg = new RegExp(smallmodrole, "ig");
        smallmods = moderators.replace(smallmodreg, smallmodtag);
    }
	//Replace individual users
	let zackman = smallmods.replace(/@zackman0010/ig, "<@129357924324605952>");
	let arty = zackman.replace(/@articulating/ig, "<@180898179502309376>");
	let juicy = arty.replace(/@juicyjuuce/ig, "<@143539331615817729>");
	return juicy;
}

//Uses different functions to send a message depending on Discord version
function version_send(channel, text = null, type = "message", options = {}) {
	let version_list = Discord.version.split(".");
	let major = parseInt(version_list[0]);
	options["split"] = {prepend: "...", append: "..."}
	if (major < 11) {
		//Use the now depreciated commands
		switch (type) {
			case "message":
				return channel.sendMessage(text, options);
				break;
			case "code":
				return channel.sendCode(options["code"], text, options);
				break;
			case "embed":
				let embed = options["embed"];
				delete options["embed"];
				return channel.sendEmbed(embed, text, options);
				break;
			case "file":
				let file = options["file"];
				delete options["file"];
				return channel.sendFile(file["attachment"], file["name"], text, options)
				break;
			case "files":
				let files = options["files"];
				delete options["files"];
				return channel.sendFiles(files, text, options);
				break;
		}
	} else {
		//Use the new command
		return channel.send(text, options);
	}
}

//Creates a new force for a PvP server
function handleNewForce(serverid, forcename) {
	let guild = bot.guilds.get(guildid);
	let pvpid = serverid + "-" + forcename;
	//Get the name to tag the server as
	let servername = savedata.channels[serverid].name;
	let pvpname = servername + "-" + forcename.replace(/ /g, "-");
	//Create the new channels, text then voice
	let create_text = guild.createChannel("factorio-" + pvpname, "text");
	let create_voice = guild.createChannel("Factorio " + servername + " " + forcename, "voice");
	//Create the role
	let create_role = guild.createRole({ name: pvpid });
	//Wait for all to be created
	Promise.all([create_text, create_voice, create_role]).then((values) => {
		let textchannel = values[0];
		let voicechannel = values[1];
		let role = values[2];
		version_send(textchannel, "Messages from force *" + forcename + "* on server *" + serverid + "* will now be sent to this channel with the prefix [" + servername + "-" + forcename + "].\n");
		savedata.channels[serverid].forceids.push(pvpid);
		savedata.channels[serverid].forcenames.push(forcename);
		//Set up text channel
		if (update_descriptions) textchannel.setTopic("Server registered");
		textchannel.setPosition(guild.channels.get(savedata.channels[serverid].id).position);
		textchannel.overwritePermissions(bot.user.id, { 'READ_MESSAGES': true }); //Allow bot to read
		textchannel.overwritePermissions(guild.roles.get(guild.roles.find("name", modrole).id), { 'READ_MESSAGES': true }); //Allow Moderators to read
		textchannel.overwritePermissions(guild.roles.get(role.id), { 'READ_MESSAGES': true }); //Allow force to read
		textchannel.overwritePermissions(guild.roles.get(guildid), { 'READ_MESSAGES': false }); //Don't allow anyone else to read
		//Set up voice channel
		voicechannel.setPosition(2);
		voicechannel.overwritePermissions(bot.user.id, { 'CONNECT': true }); //Allow bot to connect
		voicechannel.overwritePermissions(guild.roles.get(guild.roles.find("name", modrole).id), { 'CONNECT': true }); //Allow Moderators to connect
		voicechannel.overwritePermissions(guild.roles.get(role.id), { 'CONNECT': true }); //Allow force to connect
		voicechannel.overwritePermissions(guild.roles.get(guildid), { 'CONNECT': false }); //Don't allow anyone else to connect
		savedata.channels[pvpid] = { id: textchannel.id, name: pvpname, type: "pvp", main: serverid, role: role.id, voiceid: voicechannel.id, status: "alive" };
		fs.unlinkSync("savedata.json");
		fs.writeFileSync("savedata.json", JSON.stringify(savedata));
	});
}

//Delete a force from a PvP server
function deleteForce(pvpid) {
	let guild = bot.guilds.get(guildid);
	guild.channels.get(savedata.channels[pvpid].id).delete();
	guild.channels.get(savedata.channels[pvpid].voiceid).delete();
	guild.roles.get(savedata.channels[pvpid].role).delete();
	let server = pvpid.substring(0, pvpid.indexOf("-"));
	let force = pvpid.substring(pvpid.indexOf("-") + 1);
	savedata.channels[server].forceids.splice(savedata.channels[server].forceids.indexOf(pvpid), 1);
	savedata.channels[server].forcenames.splice(savedata.channels[server].forcenames.indexOf(force), 1);
	delete savedata.channels[pvpid];
	fs.unlinkSync("savedata.json");
	fs.writeFileSync("savedata.json", JSON.stringify(savedata));
}

//Arguments are two arrays that need to be compared
//Returns an Object with keys first, both, and second.
//Items that are in the first array but not the second are put into 'first'
//Items that are in the second array but not the first are put into 'second'
//Items that are in both are put into 'both'
function compareArrays(arr1, arr2) {
	let result = { first: [], both: [], second: [] };
	for (let i = 0; i < arr1.length; i++) {
		if (arr2.includes(arr1[i])) {
			//If the element is in the both arrays, push it into 'both' and splice it out of the second array
			arr2.splice(arr1[i], 1);
			result.both.push(arr1[i]);
		} else {
			//If the element is only in the first array, put it into 'first'
			result.first.push(arr1[i]);
		}
	}
	//After the above for loop, whatever is left in the second array goes into 'second'
	result.second = arr2;
	return result;
}

//The list of public commands
var publiccommands = {
	"players": function (message, command) {
		if (command.length > 2) {
			version_send(message.channel, "::players can be used either alone or with a single optional argument (::players [force])");
			return;
		}
		let force_name = null;
		let current = getChannelKey(message.channel.id);
		if (current === null || savedata.channels[current].type == "chat") {
			version_send(message.channel, "This channel is not registered to any server!\n");
			return;
		}
		if (command.length == 2) force_name = command[1];
		let serverid;
		if (savedata.channels[current].type == "pvp") serverid = current.substring(0, current.indexOf("-"));
		else serverid = current;
		if (savedata.channels[serverid].status != "started") {
			version_send(message.channel, "This server is currently offline.");
			return;
		}
		let playerlist = savedata.playerlists[serverid];
		if (Object.keys(playerlist).length === 0) {
			version_send(message.channel, "No players are currently online.");
			return;
		}
		let send_message;
		if (!force_name) send_message = "Players currently online: \n\n";
		else send_message = "Players currently online on force *" + force_name + "*:\n\n";
		for (var playername in playerlist) {
			if (!force_name || playerlist[playername].force == force_name) {
				send_message = send_message + "**" + playerlist[playername].name + "**   Force: " + playerlist[playername].force + "\n";
			}
		}
		if (send_message == ("Players currently online on force *" + force_name + "*:\n\n")) {
			version_send(message.channel, "No players are currently online for force *" + force_name + "*");
			return;
		}
		version_send(message.channel, send_message);
	},
	"register": function (message, command) {
		if (command.length != 2) {
			version_send(message.channel, "Correct usage: ::register *username*");
			return;
		}
		let userid = message.author.id;
		let username = command[1].toLowerCase();
		if (getPlayerID(username) !== null) {
			version_send(message.channel, "This Factorio username has already been taken! If you believe this to be an error, please contact @Moderators");
			return;
		}
		if (savedata.registration[userid]) version_send(message.channel, "Your previously set Factorio username will be overwritten.");
		else version_send(message.channel, "Factorio username updated.");
		savedata.registration[userid] = username;
		fs.unlinkSync("savedata.json");
		fs.writeFileSync("savedata.json", JSON.stringify(savedata));
		let server = findPlayer(username);
		if (server !== null && savedata.channels[server].type == "pvp-main") {
			version_send(message.channel, "This username was detected in a currently running PvP server. Your role has been assigned to you.");
			assignRole(server, savedata.playerlists[server][username].force, userid);
		}
	},
	"servers": function (message, command) {
		if (Object.keys(savedata.channels).length === 0) {
			version_send(message.channel, "No servers are currently registered. This may be a bug, please tag Moderators.");
			return;
		}
		let servers = "List of currently running servers:\n\n";
		for (var serverid in savedata.channels) {
			let current = savedata.channels[serverid];
			if (current.type == "server" && current.status == "started") {
				servers = servers + "**" + current.name + "** is currently running. Not PvP. Current players: " + Object.keys(savedata.playerlists[serverid]).length + "\n";
			}
			if (current.type == "pvp-main" && current.status == "started") {
				servers = servers + "**" + current.name + "** is currently running. PvP. Current players: " + Object.keys(savedata.playerlists[serverid]).length + "\n";
			}
		}
		if (servers == "List of currently running servers:\n\n") {
			version_send(message.channel, "No servers are currently running.");
		} else {
			version_send(message.channel, servers);
		}
	},
	"listservers": function (message, command) {
		publiccommands.servers(message, command);
	},
	"serverlist": function (message, command) {
		publiccommands.servers(message, command);
	},
	"status": function (message, command) {
		let registered_servers = 0;
		for (var serverid in savedata.channels) {
			let current = savedata.channels[serverid];
			if (current.type == "server" || current.type == "pvp-main") registered_servers++;
		}
		version_send(message.channel, "Factorio Bot is running. There are currently " + registered_servers + " servers registered.");
	},
	"help": function (message, command) {
		version_send(message.channel, "**::players** *[force]* - Get a list of all currently connected players, must be run in a registered channel. If the optional argument force is provided, it will print players only on that force.\n\n" +
			"**::register** *username* - Register your Factorio username to your Discord account. This is required for PvP roles to be added to your account.\n\n" +
			"**::servers** - Get a list of all currently running servers, as well as the amount of players currently connected to each.\n" +
			"**::listservers** and **::serverlist** - Same function as **::servers**, included because of how often they're used as well.\n\n" + 
			"**::status** - Have the bot print a message saying that it is running correctly\n\n" + 
			"**::serverhelp** - Must have the Moderators role, shows commands related to server/channel management.\n\n" +
			"**::adminhelp** - Must have the Moderators role, can only be run in the admin channel, shows admin management commands."
		);
	}
};

//The list of Moderator only commands
var admincommands = {
	"setserver": function (message, command) {
		if (command.length < 3) {
			version_send(message.channel, "The setserver command requires 2 arguments. ::setserver serverid servername");
			return;
		}
		//Check to see if serverid is already registered
		let serverid = command[1];
		if (savedata.channels[serverid]) {
			version_send(message.channel, "Server " + serverid + " is already registered to another Discord channel! Please go ::unset the original first.\n");
			return;
		}
		//Check to see if this channel is already registered
		let current = getChannelKey(message.channel.id);
		if (current !== null) {
			version_send(message.channel, "This channel is already registered! Please use ::unset first if you want to change this.\n");
			return;
		}
		//Get the name to tag the server as
		let servername = command.slice(2).join(" ");
		savedata.channels[serverid] = { id: message.channel.id, name: servername, type: "server", status: "unknown" };
		let name_changed = message.channel.setName("factorio-" + servername);
		name_changed.then(() => {
			if (update_descriptions) message.channel.setTopic("Server registered");
		});
		fs.unlinkSync("savedata.json");
		fs.writeFileSync("savedata.json", JSON.stringify(savedata));
		savedata.playerlists[serverid] = {};
		version_send(message.channel, "Messages from server " + serverid + " will now be sent to this channel with the prefix [" + servername + "].\n");
	},
	"setchannel": function (message, command) {
		if (command.length < 3) {
			version_send(message.channel, "The setchannel command requires 2 arguments. ::setchannel channelid channelname");
			return;
		}
		//Check to see if channelid is already registered
		let channelid = command[1];
		if (savedata.channels[channelid]) {
			version_send(message.channel, "Channel " + channelid + " is already registered to another Discord channel! Please go ::unset the original first.\n");
			return;
		}
		//Check to see if this channel is already registered
		let current = getChannelKey(message.channel.id);
		if (current !== null) {
			version_send(message.channel, "This channel is already registered! Please use ::unset first if you want to change this.\n");
			return;
		}
		//Get the name to tag the server as
		let channelname = command.slice(2).join(" ");
		savedata.channels[channelid] = { id: message.channel.id, name: channelname, type: "chat" };
		fs.unlinkSync("savedata.json");
		fs.writeFileSync("savedata.json", JSON.stringify(savedata));
		version_send(message.channel, "Messages from channel " + channelid + " will now be sent to this channel with the prefix [" + channelname + "].\n");
	},
	"setpvp": function (message, command) {
		if (command.length < 3) {
			version_send(message.channel, "The setpvp command requires 2 arguments. ::setpvp serverid servername");
			return;
		}
		//Check to see if pvpid is already registered
		let serverid = command[1];
		if (savedata.channels[serverid] && savedata.channels[serverid].type != "registered") {
			version_send(message.channel, "This server is already registered to another Discord channel! Please go ::unset the original first.");
			return;
		}
		//Check to see if this channel is already registered
		let current = getChannelKey(message.channel.id);
		if (current !== null) {
			version_send(message.channel, "This channel is already registered! Please use ::unset first if you want to change this.\n");
			return;
		}
		//Get the name to tag the server as
		let servername = command.slice(2).join(" ");
		savedata.channels[serverid] = { id: message.channel.id, name: servername, type: "pvp-main", forceids: [], forcenames: [] };
		version_send(message.channel, "Shouts from any force on server *" + serverid + "* will now be sent to this channel with the prefix [" + servername + "].\n");
		if (!savedata.playerlists[serverid]) savedata.playerlists[serverid] = {};
		let name_changed = message.channel.setName("factorio-" + servername);
		name_changed.then(() => {
			if (update_descriptions) message.channel.setTopic("Server registered");
		});
		fs.unlinkSync("savedata.json");
		fs.writeFileSync("savedata.json", JSON.stringify(savedata));
	},
	"changename": function (message, command) {
		//Change the name of a server
		if (command.length != 2) {
			version_send(message.channel, "The changename command requires one argument. ::changename newname\n");
			return;
		}
		let newname = command.slice(1).join(" ");
		let current = getChannelKey(message.channel.id);
		if (current === null) {
			version_send(message.channel, "This channel is not registered to any server!\n");
			return;
		}
		if (savedata.channels[current].type == "pvp") {
			let oldname = savedata.channels[savedata.channels[current].main].name;
			savedata.channels[savedata.channels[current].main].name = newname;
			for (let i = 0; i < savedata.channels[savedata.channels[current].main].forceids.length; i++) {
				let currentserver = savedata.channels[savedata.channels[current].main].forceids[i];
				savedata.channels[currentserver].name = savedata.channels[currentserver].name.replace(oldname, newname);
				bot.channels.get(savedata.channels[currentserver].id).setName("factorio-" + savedata.channels[currentserver].name);
			}
		} else {
			let oldname = savedata.channels[current].name;
			savedata.channels[current].name = savedata.channels[current].name.replace(oldname, newname);
			bot.channels.get(savedata.channels[current].id).setName("factorio-" + newname);
		}
		fs.unlinkSync("savedata.json");
		fs.writeFileSync("savedata.json", JSON.stringify(savedata));
	},
	"unset": function (message, command) {
		//Check to see if the server is registered to a channel
		let remove = getChannelKey(message.channel.id);
		if (remove === null) {
			version_send(message.channel, "There is nothing registered to this channel");
			return;
		}
		if (savedata.channels[remove].type == "pvp") {
			version_send(message.channel, "PvP force channels are automatically managed by the bot. Unsetting a PvP force channel is not allowed.");
			return;
		}
		if (savedata.channels[remove].type == "pvp-main") {
			let forceids = savedata.channels[remove].forceids;
			for (let i = 0; i < forceids.length; i++) {
				deleteForce(forceids[i]);
			}
		}
		//Delete the server registration and update the channel_list.json
		delete savedata.channels[remove];
		if (savedata.playerlists[remove]) delete savedata.playerlists[remove];
		fs.unlinkSync("savedata.json");
		fs.writeFileSync("savedata.json", JSON.stringify(savedata));
		version_send(message.channel, "Successfully unregistered.\n");
		let name_changed = message.channel.setName("factorio-unset");
		name_changed.then(() => {
			if (update_descriptions) message.channel.setTopic("::unset was used here");
		});
	},
	"setadmin": function (message, command) {
		//Set the admin warning messages to be delivered to this current channel
		let current = getChannelKey(message.channel.id);
		if (current !== null) version_send(message.channel, "The admin channel is currently already set. This command will overwrite the previous admin channel.\n");
		savedata.channels.admin = { id: message.channel.id, name: "Admin", type: "admin" };
		fs.unlinkSync("savedata.json");
		fs.writeFileSync("savedata.json", JSON.stringify(savedata));
		version_send(message.channel, "All Admin warnings and messages will now be sent here.\n");
	},
	"sendadmin": function (message, command) {
		if (savedata.channels.admin) {
			if (savedata.channels.admin.id == message.channel.id) {
				if (command.length < 3) {
					version_send(message.channel, "Correct usage: ::sendadmin [serverid/all] command");
					return;
				} 
				let server = command[1];
				if (savedata.channels[server] || server == "all") {
					let sendcommand = command.slice(2).join(" ");
					let sendstring = "admin$" + server + "$/silent-command local s,e = pcall(loadstring('" + clean_message(sendcommand) + "')) e = e ~= nil and print('output$ ' .. tostring(e))\n";
					safeWrite(sendstring);
				} else {
					version_send(message.channel, "Serverid is not a registered server or 'all'.");
				}
				return;
			}
		}
		version_send(message.channel, "Admin commands can only be done from the registered admin channel. Use ::setadmin to register one if you haven't already.");
	},
	"adminannounce": function (message, command) {
		if (savedata.channels.admin) {
			if (savedata.channels.admin.id == message.channel.id) {
				if (command.length < 3) {
					version_send(message.channel, "Correct usage: ::adminannounce [serverid/all] announcement");
					return;
				}
				let server = command[1];
				if (savedata.channels[server] || server == "all") {
					let announcement = command.slice(2).join(" ");
					let sendstring = "admin$" + server + "$/silent-command game.print('[ANNOUNCEMENT] " + clean_message(announcement) + "')" + "\n";
					safeWrite(sendstring);
				} else {
					version_send(message.channel, "Serverid is not a registered server or 'all'.");
				}
				return;
			}
		}
		version_send(message.channel, "Admin commands can only be done from the registered admin channel. Use ::setadmin to register one if you haven't already.");
	},
	"registerserver": function (message, command) {
		if (savedata.channels.admin) {
			if (savedata.channels.admin.id == message.channel.id) {
				if (command.length != 2) {
					version_send(message.channel, "Correct usage: ::registerserver serverid");
					return;
				}
				let serverid = command[1];
				if (savedata.channels[serverid]) {
					version_send(message.channel, "This server is already registered!");
				} else {
					savedata.channels[serverid] = { id: null, name: null, type: "registered" };
					version_send(message.channel, "Server " + serverid + " has been registered.");
					fs.unlinkSync("savedata.json");
					fs.writeFileSync("savedata.json", JSON.stringify(savedata));
				}
				return;
			}
		}
		version_send(message.channel, "Admin commands can only be done from the registered admin channel. Use ::setadmin to register one if you haven't already.");
	},
	"unregister": function (message, command) {
		if (savedata.channels.admin) {
			if (savedata.channels.admin.id == message.channel.id) {
				if (command.length != 2) {
					version_send(message.channel, "Correct usage: ::unregister serverid");
					return;
				}
				let serverid = command[1];
				if (!savedata.channels[serverid]) {
					version_send(message.channel, "This server is not registered!");
				} else {
					if (savedata.channels[serverid].type != "registered") {
						version_send(message.channel, "This server was not registered with ::registerserver. This command will not work for this server.");
					} else {
						delete savedata.channels[serverid];
						version_send(message.channel, "Server " + serverid + " has been unregistered.");
						fs.unlinkSync("savedata.json");
						fs.writeFileSync("savedata.json", JSON.stringify(savedata));
					}
				}
				return;
			}
		}
		version_send(message.channel, "Admin commands can only be done from the registered admin channel. Use ::setadmin to register one if you haven't already.");
	},
	"banhammer": function (message, command) {
		if (savedata.channels.admin) {
			if (savedata.channels.admin.id == message.channel.id) {
				if (command.length < 2) {
					version_send(message.channel, "Correct usage: ::banhammer username [reason]");
					return;
				}
				let username = command[1];
				var reason;
				if (command.length > 2) reason = command.slice(2).join(" ") + " - " + banreason;
				else reason = banreason;
				let sendstring = "admin$all$/ban " + username + " '" + reason + "'\n";
				safeWrite(sendstring);
				version_send(message.channel, "Player " + username + " has been banned from all currently running servers.\n");
				return;
			}
		}
		version_send(message.channel, "Admin commands can only be done from the registered admin channel. Use ::setadmin to register one if you haven't already.");
	},
	"unban": function (message, command) {
		if (savedata.channels.admin) {
			if (savedata.channels.admin.id == message.channel.id) {
				if (command.length != 2) {
					version_send(message.channel, "Correct usage: ::unban username");
					return;
				}
				let username = command[1];
				let sendstring = "admin$all$/unban " + username + "\n";
				safeWrite(sendstring);
				version_send(message.channel, "Player " + username + " has been unbanned from all currently running servers.\n");
				return;
			}
		}
		version_send(message.channel, "Admin commands can only be done from the registered admin channel. Use ::setadmin to register one if you haven't already.");
	},
	"restart": function (message, command) {
		if (savedata.channels.admin) {
			if (savedata.channels.admin.id == message.channel.id) {
				safeWrite("restart$\n");
				return;
			}
		}
		version_send(message.channel, "Admin commands can only be done from the registered admin channel. Use ::setadmin to register one if you haven't already.");
	},
	"clearservers": function (message, command) {
		if (savedata.channels.admin) {
			if (savedata.channels.admin.id == message.channel.id) {
				savedata.channels = {};
				fs.unlinkSync("savedata.json");
				fs.writeFileSync("savedata.json", JSON.stringify(savedata));
				return;
			}
		}
		version_send(message.channel, "Admin commands can only be done from the registered admin channel. Use ::setadmin to register one if you haven't already.");
	},
	"removeregistration": function (message, command) {
		if (savedata.channels.admin) {
			if (savedata.channels.admin.id == message.channel.id) {
				if (command.length != 2) {
					version_send(message.channel, "Correct usage: ::removeregistration *Factorio_username*");
					return;
				}
				let username = command[1];
				let userid = getPlayerID(username);
				if (userid === null) {
					version_send(message.channel, "That username is not registered!");
				} else {
					delete savedata.registration[userid];
					version_send(message.channel, "Username is now unregistered.");
					fs.unlinkSync("savedata.json");
					fs.writeFileSync("savedata.json", JSON.stringify(savedata));
				}
				return;
			}
		}
		version_send(message.channel, "Admin commands can only be done from the registered admin channel. Use ::setadmin to register one if you haven't already.");
	},
	"viewregistration": function (message, command) {
		if (savedata.channels.admin) {
			if (savedata.channels.admin.id == message.channel.id) {
				if (command.length != 2) {
					version_send(message.channel, "Correct usage: ::viewregistration *Factorio_username*");
					return;
				}
				let username = command[1];
				let userid = getPlayerID(username);
				if (userid === null) {
					version_send(message.channel, "That username is not registered!");
				} else {
					let tag = "<@" + userid + ">";
					version_send(message.channel, "That username is registered to " + tag);
				}
				return;
			}
		}
		version_send(message.channel, "Admin commands can only be done from the registered admin channel. Use ::setadmin to register one if you haven't already.");
	},
	"serverhelp": function (message, command) {
		version_send(message.channel, "***SERVER/CHANNEL MANAGEMENT:*** \n\n\n" +
			"**::setserver** *serverid servername* - Any messages internally tagged with serverid will be sent to the channel this command is run in, prefixed with '[servername]'.\n\n" +
			"**::setchannel** *channelid channelname* - Same as above, but using chat channels (coded by Articulating) rather than servers.\n\n" +
			"**::setpvp** *serverid* *servername* - Set the main channel for a PvP server. If the bot interface is used in-game, it will auto-create any force specific channels as needed at the beginning of each round.\n\n" +
			"**::changename** *newname* - Change the registered name of a server, must be done in the channel you wish to change. If done to a PvP channel, it will change the name of all PvP channels connected to the same server.\n\n" +
			"**::unset** - Unsets a channel that was previously registered using ::setserver, ::setchannel, or ::setpvp. Unsetting a single force PvP channel will only unset that channel, but unsetting the main PvP channel will unset all force specific channels.\n\n"
		);
	},
	"adminhelp": function (message, command) {
		if (savedata.channels.admin && savedata.channels.admin.id != message.channel.id) {
			version_send(message.channel, "Admin commands can only be done from the registered admin channel. Use ::setadmin to register one if you haven't already.");
			return;
		}
		version_send(message.channel, "***ADMIN CHANNEL COMMANDS:*** \n (All commands must be run in the registered admin channel) \n\n\n" +
			"**::setadmin** - Sets the channel that all admin warnings and messages are to be delivered to.\n\n" +
			"**::sendadmin** *[serverid/all] command* - Sends 'command' to 'serverid' as if you were typing directly into the console (/silent-command will automatically be attached to the beginning). " +
			"Replace serverid with \"all\" to send to all running servers. Serverid must be registered.\n\n" +
			"**::adminannounce** *[serverid/all] announcement* - Sends an announcement to 'serverid'. Replace serverid with \"all\" to send to all running servers. Serverid must be registered.\n\n" +
			"**::registerserver** *serverid* - Register a server for use, but do not attach a Discord channel to it. (Allows ::sendadmin and ::adminanounce to work).\n\n" +
			"**::unregister** *serverid* - Unregister a server registered with ::registerserver.\n\n" +
			"**::banhammer** *username* - Bans a player from all running servers at once.\n\n" +
			"**::unban** *username* - Unbans a player from all running servers at once.\n\n" +
			"**::restart** - Have the bot restart, allowing any updates to the source code to occur without requiring shutting down everything else.\n\n" +
			"**::clearservers** - Delete and recreate a blank channel_list.json. This will unregister every server, including the admin channel. Used in case an update changes the structure of channel_list.json.\n\n" +
			"**::removeregistration** *Factorio_username* - Remove a username from the registration list.\n\n" +
			"**::viewregistration** *Factorio_username* - View the user that a certain username is registered to."
		);
	}
};

//Commands available to the smallmodrole
var limitedadmincommands = {
	"banhammer": function (message, command) {
		admincommands.banhammer(message, command);
	},
	"sendadmin": function (message, command) {
		admincommands.sendadmin(message, command)
	},
	"adminhelp": function (message, command) {
		if (savedata.channels.admin && savedata.channels.admin.id != message.channel.id) {
			version_send(message.channel, "Admin commands can only be done from the registered admin channel. Use ::setadmin to register one if you haven't already.");
			return;
		}
		if (hasRole(message, modrole)) {
			admincommands.adminhelp(message, command);
			return;
		}
		version_send(message.channel, "***ADMIN CHANNEL COMMANDS:*** \n (All commands must be run in the registered admin channel) \n" +
			"As you are a limited admin user, you only have access to a subset of the full admin commands:\n\n\n" +
			"**::sendadmin** *[serverid/all] command* - Sends 'command' to 'serverid' as if you were typing directly into the console (/silent-command will automatically be attached to the beginning). " +
			"Replace serverid with \"all\" to send to all running servers. Serverid must be registered.\n\n" +
			"**::banhammer** *username* - Bans a player from all running servers at once.\n\n"
		);
	}
};

//Update channel description with current list of players
function updateDescription(channelid) {
	if (!update_descriptions) return;
	var playerliststring;
	let serverid;
	let force_name;
	if (savedata.channels[channelid].type == "pvp") {
		serverid = channelid.substring(0, channelid.indexOf("-"));
		force_name = channelid.substring(channelid.indexOf("-") + 1);
		playerliststring = "Server online. ## Connected players (Force " + force_name + "): ";
	} else {
		serverid = channelid;
		force_name = null;
		playerliststring = "Server online. ## Connected players: ";
	}
	let playerlist = savedata.playerlists[serverid];
	var playerlistcount = 0;
	if (Object.keys(playerlist).length === 0) {
		if (!force_name) bot.channels.get(savedata.channels[channelid].id).setTopic("Server online. No players connected");
		else bot.channels.get(savedata.channels[channelid].id).setTopic("Server online. No players connected (Force " + force_name + ")");
		return;
	}
	for (var playername in playerlist) {
		if (!force_name || playerlist[playername].force == force_name) {
			playerliststring = playerliststring + playerlist[playername].name + ", ";
			playerlistcount++;
		}
	}
	if (playerlistcount === 0) {
		bot.channels.get(savedata.channels[channelid].id).setTopic("Server online. No players connected (Force " + force_name + ")");
		return;
	}
	let preparestring = playerliststring.substring(0, playerliststring.length - 2)
	let finalstring = preparestring.replace("##", playerlistcount);
	bot.channels.get(savedata.channels[channelid].id).setTopic(finalstring);
}

//Set utf8 encoding for both stdin and stdout
process.stdin.setEncoding('utf8');
process.stdout.setDefaultEncoding('utf8');

function handleInput(input) {
	//Get the channelid
	let separator = input.indexOf("$");
	let channelid = input.substring(0, separator);
	if (channelid == "heartbeat") {
		//Heartbeat function to insure the bot is still running, meant to be functionless
		return;
	} else if (channelid == "emergency") {
		//Bot crashed, must restart
		if (!savedata.channels.admin) return;
		let roleid = bot.guilds.get(guildid).roles.find("name", modrole).id;
		let tag = "<@&" + roleid + ">";
		let new_input = input.substring(separator + 1);
		version_send(bot.channels.get(savedata.channels.admin.id), tag + " The bot has crashed! The crash was detected and the bot restarted at " + new_input + "\n");
	} else if (channelid == "crashreport") {
		//Bot crashed, must restart
		if (!savedata.channels.admin) return;
		let roleid = bot.guilds.get(guildid).roles.find("name", modrole).id;
		let tag = "<@&" + roleid + ">";
		let servername = input.substring(separator + 1);
		if (!savedata.channels[servername]) return;
		version_send(bot.channels.get(savedata.channels.admin.id), tag + " Server *" + servername + "* (" + savedata.channels[servername].name + ") has crashed!\n");
		let message_sent = version_send(bot.channels.get(savedata.channels[servername].id), "**Server crash was detected. Moderators have been notified. Please wait for restart.**");
		//message_sent.then((message) => {
		//	message.channel.overwritePermissions(bot.guilds.get(guildid).roles.get(guildid), { 'SEND_MESSAGES': false });
		//});
		savedata.channels[servername].status = "stopped";
		delete savedata.playerlists[servername];
		fs.unlinkSync("savedata.json");
		fs.writeFileSync("savedata.json", JSON.stringify(savedata));
	} else if (channelid == "admin") {
		//Admin Warning System
		if (!savedata.channels.admin) return;
		let roleid = bot.guilds.get(guildid).roles.find("name", modrole).id;
		let tag = "<@&" + roleid + ">";
		let new_input = input.substring(separator + 1);
		separator = new_input.indexOf("$");
		channelid = new_input.substring(0, separator);
		let channelname = savedata.channels[channelid].name;
		let message = new_input.substring(separator + 1);
		version_send(bot.channels.get(savedata.channels.admin.id), 
			tag + "\n" +
			"**Admin Warning System was set off!**\n" +
			"Server ID: " + channelid + "\n" +
			"Server Name: " + channelname + "\n" +
			"Message: " + message
		);
	} else if (channelid == "output") {
		//Requested output from server being returned
		if (!savedata.channels.admin) return;
		let message = input.substring(separator + 1);
		version_send(bot.channels.get(savedata.channels.admin.id), "Response: " + message + "\n");
	} else if (channelid == "query") {
		let new_input = input.substring(separator + 1);
		separator = new_input.indexOf("$");
		let uid = new_input.substring(0, separator);
		if (!queryResponse[uid]) return;
		let response = new_input.substring(separator + 1);
		resolveQuery(uid, response);
	} else if (channelid == "PLAYER") {
		//Player Update
		let new_input = input.substring(separator + 1);
		separator = new_input.indexOf("$");
		channelid = new_input.substring(0, separator);
		if (savedata.channels[channelid]) {
			let data = new_input.substring(separator + 1).split(","); //Replaces the newline at the end while also splitting the arguments apart
			let action = data[0]; //Join,Leave,Force,Die,Respawn
			let player_id = data[1]; //Not really relevant, but included in case it may be needed sometime in the future
			let cap_name = data[2]; //Player's username (Capitalized)
			let player_name = cap_name.toLowerCase(); //Player's username (Lowercase)
			let force_name = data[3]; //Name of player's force
			var message;
			var old_force;
			if (savedata.playerlists[channelid][player_name]) old_force = savedata.playerlists[channelid][player_name].force;
			else old_force = null;

			switch (action) {
				case "join":
					message = "**Player " + cap_name + " has joined the server!**";
					savedata.playerlists[channelid][player_name] = { "name": cap_name, "force": force_name };
					break;
				case "leave":
					message = "**Player " + cap_name + " has left the server!**";
					delete savedata.playerlists[channelid][player_name];
					break;
				case "force":
					message = "**Player " + cap_name + " has joined force " + force_name + "!**";
					if (!savedata.playerlists[channelid][player_name]) return;
					savedata.playerlists[channelid][player_name].force = force_name;
					break;
				case "die":
					message = "**Player " + cap_name + " was killed!**";
					if (!savedata.playerlists[channelid][player_name]) return;
					savedata.playerlists[channelid][player_name].force = force_name;
					break;
				case "respawn":
					message = "**Player " + cap_name + " just respawned!**";
					if (!savedata.playerlists[channelid][player_name]) return;
					savedata.playerlists[channelid][player_name].force = force_name;
					break;
				case "update":
					savedata.playerlists[channelid][player_name] = { "name": cap_name, "force": force_name }
					return;
			}
			message = message.replace(/_/g, "\\_");
			fs.unlinkSync("savedata.json");
			fs.writeFileSync("savedata.json", JSON.stringify(savedata));
			if (savedata.channels[channelid].type == "pvp-main") {
				let userid = getPlayerID(player_name);
				if (userid !== null) {
					if (action == "join") assignRole(channelid, force_name, userid);
					else if (action == "leave") removeRole(channelid, old_force, userid);
					else if (old_force && old_force != force_name) {
						let deleted = removeRole(channelid, old_force, userid);
						if (deleted !== null) {
							deleted.then(deletedplayer => { assignRole(channelid, force_name, deletedplayer.id); });
						} else {
							assignRole(channelid, force_name, userid);
						}
					}
				}
				updateDescription(channelid);
				version_send(bot.channels.get(savedata.channels[channelid].id), message);
				channelid = channelid + "-" + force_name;
				if (!savedata.channels[channelid]) return;
			}
			updateDescription(channelid);
			version_send(bot.channels.get(savedata.channels[channelid].id), message);
		}
	} else if (channelid == "PVPROUND") {
		let new_input = input.substring(separator + 1);
		separator = new_input.indexOf("$");
		channelid = new_input.substring(0, separator);
		if (savedata.channels[channelid] && savedata.channels[channelid].type == "pvp-main") {
			let data = new_input.substring(separator + 1).split(","); //Replaces the newline at the end while also splitting the arguments apart
			let action = data[0]; //Begin,Eliminated,End
			var message;
			switch (action) {
				case "begin":
				case "ongoing":
					let roundno = data[1];
					let forces = data.splice(2);
					let forcestring = forces.join(", ");
					let forceno = forces.length;
					message = "**[ROUND " + action.toUpperCase() + "] Round " + roundno + "; " + forceno + " Teams: " + forcestring + "**";
					//Create any forces that did not previously exist while deleting any old forces that no longer exist
					//Also set any forces that still exist to be alive again so messages can be sent
					let previousforces = savedata.channels[channelid].forcenames;
					let compare = compareArrays(previousforces, forces);
					let deletedforces = compare.first;
					let newforces = compare.second;
					let sameforces = compare.both;
					for (let i = 0; i < deletedforces.length; i++) deleteForce(channelid + "-" + deletedforces[i]);
					for (let i = 0; i < newforces.length; i++) handleNewForce(channelid, newforces[i]);
					for (let i = 0; i < sameforces.length; i++) savedata.channels[channelid + "-" + sameforces[i]].status = "alive";
					break;
				case "eliminated":
					let deadteam = data[1];
					let killer = data[2];
					if (killer == "suicide") {
						message = "**[TEAM ELIMINATED] Team " + deadteam + " has destroyed their own silo!**";
					} else if (killer == "neutral") {
						message = "**[TEAM ELIMINATED] Team " + deadteam + " has been eliminated!**";
					} else {
						message = "**[TEAM ELIMINATED] Team " + deadteam + " has been eliminated by Team " + killer + "!**";
					}
					//Set force status as dead so messages are no longer sent
					if (savedata.channels[channelid + "-" + deadteam]) savedata.channels[channelid + "-" + deadteam].status = "dead";
					break;
				case "end":
					let roundnum = data[1]; //Have to use a different name for some reason, not sure why
					let winner = data[2];
					if (data.length > 3) {
						let time = data.slice(2).join(" ").replace(";", ",");
						message = "**[ROUND END] Round " + roundnum + " has ended after " + time + "! Winner: Team " + winner + "!**";
					} else {
						message = "**[ROUND END] Round " + roundnum + " has ended! Winner: Team " + winner + "!**";
					}
					break;
			}
			fs.unlinkSync("savedata.json");
			fs.writeFileSync("savedata.json", JSON.stringify(savedata));
			version_send(bot.channels.get(savedata.channels[channelid].id), message);
		}
		return;
	} else if (savedata.channels[channelid]) {
		if (savedata.channels[channelid].type == "registered") {
			return;
		} else if (savedata.channels[channelid].type == "pvp-main") {
			let message = input.substring(separator + 1);
			if (message == "**[ANNOUNCEMENT]** Server has started!") {
				//Open the channel for chat if the server is running
				let mainserver = channelid;
				let open_server = bot.channels.get(savedata.channels[mainserver].id).overwritePermissions(bot.guilds.get(guildid).roles.get(guildid), { 'SEND_MESSAGES': true });
				open_server.then(() => {
					version_send(bot.channels.get(savedata.channels[mainserver].id), message);
				});
				if (update_descriptions) bot.channels.get(savedata.channels[mainserver].id).setTopic("Server online. No players connected");
				let forceids = savedata.channels[channelid].forceids;
				for (let i = 0; i < forceids.length; i++) {
					let insideid = forceids[i];
					let open_server = bot.channels.get(savedata.channels[insideid].id).overwritePermissions(bot.guilds.get(guildid).roles.get(guildid), { 'SEND_MESSAGES': true });
					open_server.then(() => {
						version_send(bot.channels.get(savedata.channels[insideid].id), message);
					});
					let force_name = insideid.substring(insideid.indexOf("-") + 1);
					if (update_descriptions) bot.channels.get(savedata.channels[insideid].id).setTopic("Server online. No players connected (Force " + force_name + ")");
				}
				savedata.channels[mainserver].status = "started";
				if (savedata.playerlists[mainserver]) delete savedata.playerlists[mainserver];
				savedata.playerlists[mainserver] = {};
				fs.unlinkSync("savedata.json");
				fs.writeFileSync("savedata.json", JSON.stringify(savedata));
			} else if (message == "**[ANNOUNCEMENT]** Server has stopped!") {
				//Close the channel for chat if the server is stopped
				let mainserver = channelid;
				let message_sent = version_send(bot.channels.get(savedata.channels[mainserver].id), message);
				message_sent.then((message) => {
					//message.channel.overwritePermissions(bot.guilds.get(guildid).roles.get(guildid), { 'SEND_MESSAGES': false });
				});
				let forceids = savedata.channels[channelid].forceids;
				for (let i = 0; i < forceids.length; i++) {
					let insideid = forceids[i];
					let message_sent = version_send(bot.channels.get(savedata.channels[insideid].id), message);
					message_sent.then((message) => {
						//message.channel.overwritePermissions(bot.guilds.get(guildid).roles.get(guildid), { 'SEND_MESSAGES': false });
					});
					if (update_descriptions) bot.channels.get(savedata.channels[insideid].id).setTopic("Server offline");
				}
				savedata.channels[mainserver].status = "stopped";
				delete savedata.playerlists[mainserver];
				fs.unlinkSync("savedata.json");
				fs.writeFileSync("savedata.json", JSON.stringify(savedata));
			} else {
				//Server is a PvP server, send to correct channel
				if (message.charAt(0) == "[") {
					//Message is from the web, send it to the main channel.
					version_send(bot.channels.get(savedata.channels[channelid].id), message);
				} else {
					message = replaceMentions(message);
					separator = message.indexOf(":");
					let username = message.substring(0, separator);
					message = message.replace(/_/g, "\\_");
					if (message.charAt(0) == '[') {
						//If message is from web, send it to main channel
						version_send(bot.channels.get(savedata.channels[channelid].id), message);
					} else if (username.indexOf(" (shout)") > 0) {
						//If message is a shout, send it to main channel
						username = username.replace(" (shout)", "");
						let shoutless = message.replace(" (shout):", ":");
						version_send(bot.channels.get(savedata.channels[channelid].id), "[" + savedata.channels[channelid].name + "] " + shoutless);
					} else {
						//Send non-shout message to force specific channel
						if (username.indexOf("[") != -1) username = username.substring(0, username.indexOf("[") - 1); //Remove any tag on the username
						username = username.toLowerCase(); //Convert username to lowercase
						if (!savedata.playerlists[channelid][username]) return;
						let force_name = savedata.playerlists[channelid][username].force;
						let pvp_channelid = channelid + "-" + force_name;
						if (savedata.channels[pvp_channelid]) {
							version_send(bot.channels.get(savedata.channels[pvp_channelid].id), "[" + savedata.channels[pvp_channelid].name + "] " + message);
						}
					}
				}
			}
		} else {
			//Server is not PvP, send message normally
			let message = replaceMentions(input.substring(separator + 1));
			if (message.indexOf(" (shout):") > 0 && message.indexOf(" (shout)") < message.indexOf(":")) message = message.replace(" (shout):", ":");
			if (message == "**[ANNOUNCEMENT]** Server has started!") {
				//Open the channel for chat if the server is running
				let open_server = bot.channels.get(savedata.channels[channelid].id).overwritePermissions(bot.guilds.get(guildid).roles.get(guildid), { 'SEND_MESSAGES': true });
				open_server.then(() => {
					version_send(bot.channels.get(savedata.channels[channelid].id), message);
				});
				savedata.channels[channelid].status = "started";
				if (update_descriptions) bot.channels.get(savedata.channels[channelid].id).setTopic("Server online. No players connected.");
				if (savedata.playerlists[channelid]) delete savedata.playerlists[channelid];
				savedata.playerlists[channelid] = {};
				fs.unlinkSync("savedata.json");
				fs.writeFileSync("savedata.json", JSON.stringify(savedata));
			} else if (message == "**[ANNOUNCEMENT]** Server has stopped!") {
				//Close the channel for chat if the server is stopped
				let message_sent = version_send(bot.channels.get(savedata.channels[channelid].id), message);
				message_sent.then((message) => {
					//bot.channels.get(savedata.channels[channelid].id).overwritePermissions(bot.guilds.get(guildid).roles.get(guildid), { 'SEND_MESSAGES': false });
				});
				savedata.channels[channelid].status = "stopped";
				if (update_descriptions) bot.channels.get(savedata.channels[channelid].id).setTopic("Server offline");
				delete savedata.playerlists[channelid];
				fs.unlinkSync("savedata.json");
				fs.writeFileSync("savedata.json", JSON.stringify(savedata));
			} else {
				message = message.replace(/_/g, "\\_");
				if (message.charAt(0) == '[') version_send(bot.channels.get(savedata.channels[channelid].id), message);
				else version_send(bot.channels.get(savedata.channels[channelid].id), "[" + savedata.channels[channelid].name + "] " + message);
			}
		}
	} else return;
}

//Receive input from management program
process.stdin.on('readable', () => {
	let input = process.stdin.read();

	if (input !== null) {
		let newline_input = input.replace(/\r/g, "\n");
		let split_input = newline_input.split("\n");

		for (let i = 0; i < split_input.length; i++) {
			handleInput(split_input[i]);
		}
	}
});

function hasRole(message, role) {
	return message.member.roles.has(message.guild.roles.find("name", role).id);
}

//Receive input from Discord
bot.on('message', (message) => {
	//Ignore own messages
	if (message.author == bot.user) return;
	//Ignore DMs
	if (!message.member) return;
	//Set the prefix
	let prefix = "::";

	//If message is a command, run the correct command. Else, forward to the proper server (if channel is registered)
	if (message.content.startsWith(prefix)) {
		let command = message.cleanContent.substring(2).split(" ");
		if (publiccommands[command[0].toLowerCase()]) {
			publiccommands[command[0].toLowerCase()](message, command);
			return;
		}
		if (smallmodrole && limitedadmincommands[command[0].toLowerCase()] && !hasRole(message, smallmodrole) && !hasRole(message, modrole) && !hasRole(message, adminrole)) {
			version_send(message.channel, "You do not have permission to use this command!");
			return;
		}
		if (smallmodrole && limitedadmincommands[command[0].toLowerCase()]) {
			limitedadmincommands[command[0].toLowerCase()](message, command)
			return;
		}
		if (admincommands[command[0].toLowerCase()] && !hasRole(message, modrole) && !hasRole(message, adminrole)) {
			version_send(message.channel, "You do not have permission to use this command!");
			return;
		}
		if (admincommands[command[0].toLowerCase()]) {
			admincommands[command[0].toLowerCase()](message, command);
			return;
		}
		if (command[0].toLowerCase() == "eval" && message.author.id != "129357924324605952" && !hasRole(message, adminrole)) {
			//Not zackman0010 or an Admin
			version_send(message.channel, "You do not have permission to use this command!");
			return;
		}
		if (command[0].toLowerCase() == "eval") {
			try {
				var code = command.splice(1).join(" ");
				var evaled = eval(code);
				if (typeof evaled !== "string") evaled = require("util").inspect(evaled);
				if (evaled != "undefined" && evaled != "Promise { <pending> }") version_send(message.channel, evaled.replace(new RegExp(token, 'g'), "TOKEN").replace(new RegExp(config.clientsecret, 'g'), "CLIENT_SECRET"), "code", { "code": "javascript" });
			} catch (err) {
				version_send(message.channel, `\`ERROR\` \`\`\`xl\n${err}\n\`\`\``);
			}
			return;
		}
		version_send(message.channel, "That command does not exist. Please use ::help to see the list of commands.");
		return;
	} else {
		//Get the server that matches this channel. If this channel is unregistered, the result will be null.
		let sendto = getChannelKey(message.channel.id);
		var name;
		if (message.member.nickname === null) name = message.author.username;
		else name = message.member.nickname;
		if (sendto === null) return;
		while (!safe) {
			//Wait here until safe to continue, should not happen often
		}
		var addon;
		let channel = savedata.channels[sendto];
		if (channel.type == "chat") addon = "chat$" + sendto; //Setup to send to a chat channel
		else if (channel.type == "server" || channel.type == "pvp-main") addon = sendto; //Setup to send to a server
		else if (channel.type == "pvp") {
			//Setup to send to a PVP server
			let serverid = sendto.substring(0, sendto.indexOf("-"));
			let force_name = sendto.substring(sendto.indexOf("-") + 1);
			addon = "PVP$" + serverid + "$" + force_name;
		} else return;
		var sendstring;
		if (channel.type == "pvp-main") sendstring = clean_message(addon + "$[DISCORD] " + name + " (shout): " + message.cleanContent) + "\n";
		else sendstring = clean_message(addon + "$[DISCORD] " + name + ": " + message.cleanContent) + "\n";
		if (channel.type == "chat" || (channel.type != "pvp" && channel.status != "stopped") || (channel.type == "pvp" && channel.status != "dead")) safeWrite(sendstring);
	}
});

//Leaves any server that isn't registered
bot.on('ready', () => {
	bot.user.setGame(gamemessage);
	//bot.guilds.forEach((guildobj, guildid, collection) => {
	bot.guilds.forEach((guildobj, lguildid) => {
		if (lguildid != guildid) guildobj.leave();
	});
	//Set any currently existing PvP servers back up for fresh player lists
	for (var key in savedata.channels) {
		if ((savedata.channels[key].type == "pvp-main" || savedata.channels[key].type == "server") && !savedata.playerlists[key]) savedata.playerlists[key] = {};
	}
	fs.unlinkSync("savedata.json");
	fs.writeFileSync("savedata.json", JSON.stringify(savedata));
	safeWrite("ready$\n");
});

//If the bot joins a server that isn't registered, immediately leave it
bot.on('guildCreate', (guild) => {
	if (guild.id != guildid) guild.leave();
});

//Bot disconnect detection
bot.on('disconnect', (event) => {
	//Start the error string
	let sendstring = "DEBUG$Disconnect detected. ";
	//Check for error code
	if (event.code) sendstring = sendstring + "Error Code: " + event.code + ". ";
	else sendstring = sendstring + "No Error Code provided. ";
	//Check for error reason
	if (event.reason) sendstring = sendstring + "Reason: " + event.reason + ". ";
	else sendstring = sendstring + "No Reason provided. ";
	//Check if disconnect was clean
	if (event.wasClean) sendstring = sendstring + "Disconnect was clean.";
	else sendstring = sendstring + "Disconnect was not clean.";
	//Write the string to the log
	safeWrite(sendstring);
});

bot.login(token);

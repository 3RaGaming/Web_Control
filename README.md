# Web_Control
Web accessible server management. Start/Stop servers, upload/delete save files, chat with active servers, edit server settings, download log files, and more! Discord auth is available for web control logins. There are two levels of login: Admin (can update Web-Control with click of a button) and Moderator (cannot update web-control). All user actions are logged, and log files cannot be removed from the web interface. More permission adjustment is in the works. View our trello (https://trello.com/b/QP2fuOXj/web-control) for project status and plans. A more detailed guide to the web control is in the works here: http://3ragaming.com/faq/web_control/

Game, Web server, and discord bot must on the same server (for now).

# Requirements
configure the sudoers file to allow www-data access to screen and gcc or else you will be unable to start the factorio server from the web control

`www-data ALL=(ALL:ALL) /usr/bin/screen *`

`www-data ALL=(ALL:ALL) /usr/bin/gcc *`


Easy Install! Put this line into your SSH terminal to begin the install:

`bash <(curl -s https://raw.githubusercontent.com/3RaGaming/Web_Control/master/install.sh)`

This will run you through the entire setup process. Once the program is installed on the server, you'll be instructed on how to access the web gui to continue the rest of the configuration.

# Dependencies

Ubuntu 20.04 (or any other linux of your choosing, if you have the know-how to figure it out)

Apache2 with SSL Enabled. (Web Control is currently set to only work on a an https connection)

php7 with cURL, zip, json

gnu "screen" (apt install screen)

gcc and npm

zip, unzip, tar, and xz-utils

crontab (apt install cron, specifically)

Node.js v16.4.0 or higher (https://nodejs.org/en/download/package-manager/#debian-and-ubuntu-based-linux-distributions)

Since we run with GNU Screen, php will need sudo access to function correctly this requirement is partially why a docker install is nice

# Manual Installation

If you prefer to do it manually, here are the steps. Right now the file path dependencies are as follows:  
/var/www/html for the web files  
/var/www/factorio for the server save locations.  
/usr/share/factorio/1.1.53 for the factorio instance itself
(each factorio server version should be in it's own appropriately named folder)
Basically, you should treat /var/www/ as the root directory for all web_control repo files.

To compile the manage.c program, you must install gcc.  
1) Open a Terminal window and navigate to `cd /var/www/factorio`

2) Run the command `gcc -o managepgm -std=gnu99 -pthread manage.c` (On success, nothing should appear in the terminal. If an error message appears, message zackman0010 with the error message.)

3) Run the command `npm i --save --no-optional discord.js` (If a message appears saying missing requirements, ignore it. It's only the voice server parts, which are not used in this program)

Once the server files are all installed, and you have web access, there is a button at the top of the page to update from the master repo. This will easily keep your server up to date.
We recommend following our updates, as if a recompile of the manage.c is ever necessary, you may need to restart your factorio servers.

# Docker Build Installation

`docker build -t factorio .`  
`docker run -dt --restart unless-stopped --name factorio factorio` Exposed ports are set in the docker file. If you need alternate ones, edit the docker file, or add them individually via `-p 8080:80/tcp` with the correct ports needed  
Nginx runs on port 8080 by default, so you'll need another proxy or port forward to expose this. This docker build uses 80 for unencrypted traffic only. If you want ssl, it's suggested to use an nginx reverse proxy that will handle the ssl. You'll also want to set the `client_max_body_size 100M;` setting in your config to allow large files to be uploaded.
`docker exec -it factorio /bin/bash` Login to the server to check things  

# Finishing touches

This install is currently dependant on a discord bot to function. You need to update `/var/www/factorio/config.json` before starting any factorio servers.  
You also need to update your factorio username and password(or, not both)token in each `/var/www/factorio/serverX/server-settings.json` file. We don't have easier forms for these yet.
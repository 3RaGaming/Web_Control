# Web_Control
Quick and dirty web gui used to control the game servers. Game and Apache are run on the same server, for now.

# Requirements
(Realized this was kind of important... so we're working on it)

configure the sudoers file to allow www-data access to screen and gcc
`www-data ALL=(ALL:ALL) /usr/bin/screen *
www-data ALL=(ALL:ALL) /usr/bin/gcc *`
Or else you will be unable to start the factorio server from the web control

Ubuntu 16.06 (or any other linux of your choosing, if you have the know-how to figure it out)

Apache2 with SSL Enabled. (Web Control is set to only work on a an https connection)

php7 with cURL

Node.js (https://nodejs.org/en/download/package-manager/#debian-and-ubuntu-based-linux-distributions)

# Installation
Easy Install! Put this line into your SSH terminal to begin the install:
`bash <(curl -s https://raw.githubusercontent.com/3RaGaming/Web_Control/master/install.sh)`

This will run you through the entire setup process. Once the program is installed on the server, you'll be instructed on how to access the web gui to continue the rest of the configuration.

Or if you prefer to do it manually, here are the steps.
Right now the file path dependancies are as follows:  
/var/www/html for the web files  
/var/www/factorio for the server save locations.  
/usr/share/factorio/ for the factorio instance itself  
Basically, you should treat /var/www/ as the root directory for all web_control files.

To compile the manage.c program, you must install gcc.  
1) Open a Terminal window and navigate to `cd /var/www/factorio`

2) Run the command `gcc -o -std=gnu99 managepgm -pthread manage.c` (On success, nothing should appear in the terminal. If an error message appears, message zackman0010 with the error message.)

3) Run the command `npm i --save --no-optional discord.js` (If a message appears saying missing requirements, ignore it. It's only the voice server parts, which are not used in this program)

Once the server files are all installed, and you have web access, there is a button at the top of the page to update from the master repo. This will easily keep your server up to date.
We reccomend following our updates, as if a recompile of the manage.c is ever necessary, you may need to restart your factorio server(s).

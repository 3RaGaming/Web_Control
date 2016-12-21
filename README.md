# Web_Control
Quick and dirty web gui used to control the game servers. Game and Apache are run on the same server, for now.

# Requirements
(Realized this was kind of important... so we're working on it)

Ubuntu 16.06 (or any other linux of your choosing, if you have the know-how to figure it out)

Apache2 with SSL Enabled. (Web Control is set to only work on a an https connection)

php7

Node.js (https://nodejs.org/en/download/package-manager/#debian-and-ubuntu-based-linux-distributions)

# Installation

1) Download the repo and extract it somewhere on your desktop

2) Open a Terminal window and navigate to (Where you extracted the program)/Web_Control/factorio

3) Run the command `gcc -o managepgm -pthread manage.c` (On success, nothing should appear in the terminal. If an error message appears, message zackman0010 with the error message.)

4) Run the command `npm i --save --no-optional discord.js` (If a message appears saying missing requirements, ignore it. It's only the voice server parts, which are not used in this program)

5) Servers can be controlled from the webserver using its interface

# In Development
Organize code so it's more uniform and compatible to be put anywhere in a template (almost done. This will be the last feature update to this version of the master)

We are in development of Discord bot integreation to factorio. A new repo will handle the discord bot itself, and a new branch in this repo (dev-bot) will handle the integration of Discord bot to the Web Control. Since this master version of web control work's as is, it will remain for others to use. Once dev-bot can handle being used with or without the discord bot, then this master will be replaced. Bug-fixes will still be applied to this master, but new features will be implemente only to 'dev' and 'dev-bot'. Consider 'dev-bot' as the "unstable" master version of Web_Control

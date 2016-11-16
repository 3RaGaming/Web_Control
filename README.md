# Web_Control
Quick and dirty web gui used to control the game servers. Game and Apache are run on the same server, for now.

# Requirements
(Realized this was kind of important... so we're working on it)

Ubuntu 16.06 (or any other linux of your choosing, if you have the know-how to figure it out)

Apache2

php7

Node.js (https://nodejs.org/en/download/package-manager/#debian-and-ubuntu-based-linux-distributions)

# Installation

1) Download the repo and extract it somewhere on your desktop

2) Open a Terminal window and navigate to (Where you extracted the program)/Web_Control/factorio

3) Run the command 'gcc -o manage -pthread manage.c'

4) Run the command 'npm i --save --no-optional discord.js' (If a message appears saying missing requirements, ignore it. It's only the voice server parts, which are not used in this program)

5) Servers can be controlled from the webserver using its interface

# In Development
Add delete/replace option for files. (almost done)

Organize code so it's more uniform and compatible to be put anywhere in a template (almost done)

Verify player-data.json

Verify config.ini

Create archive option for save files.

Better version control to update factorio/purge old factorio instances. (could just do an update, which a python git already exists for, but want more flexability for downgrades in the event an experimental version is badly bugged.)

MapGen and git-linking

Allow selecting which file to start the server with (manual override of load-latest)

Use javascript to ajax stream everything! Better updates to push to multiple admins/mods online.

Syncronized server banning.

production reports and a public front end for player viewing. Even if we tie this front end into our main godaddy page for better control and display.


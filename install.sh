#!/bin/bash
if [ "$EUID" -ne 0 ]
	then printf "Please run as root\n"
	exit
fi

install_dir="/usr/share/factorio"
#install_dir="/root/test"

#compressed file extraction function. 0.14 is in tar.gz, and .15 is in tar.xz, for some reason.
function extract () {
	if [ -f $1 ] ; then
		case $1 in
			*.tar.gz)  tar --strip-components=1 -xzf $1 -C $2/$3; printf "Done!\n";;
			*.tar.xz)  tar --strip-components=1 -xf $1 -C $2/$3; printf "Done!\n";;
			*)         printf "Unknown compressiong type can't extract from $1\n"; break;;
		esac
	else
		printf "Unable to access file $1... We'll deal with that later...\n"
	fi
}

#version checker. Will need this in the case node is already installed
function version_gt() {
	test "$(printf '%s\n' "$@" | sort -V | head -n 1)" != "$1";
}

printf "Welcome to 3Ra Gaming's Factorio Web Control Installer!\n\n"
#printf "This tool will automatically check that all required dependancies are installed\n"
#printf "If any are not yet installed, it will attempt to install them.\n\n"
printf "This script should verify all dependancies and will attempt to install them. You will be asked before each dependency is installed.\n"
while true; do
	read -p "Are you currently running Ubuntu 16.04 or higher?  " yn
	case $yn in
		[Yy]* ) break;;
		[Nn]* )
			printf "\nUnfortunately, this installer was built for Ubuntu :(\n\n";
			printf "Please consult the github for manual instructions\n";
			printf "http://www.3ragaming.com/github\n";
			printf "You may also join our Discord and we will do our best to assist you\n";
			printf "http://www.3ragaming.com/discord\n\n";
			exit;;
		* ) echo "Please answer yes[Y] or no[N].";;
	esac
done

#Define dependencies
#depend_arr+=("");
depend_arr=();
depend_arr+=("curl");
depend_arr+=("zip");
depend_arr+=("unzip");
depend_arr+=("tar");
depend_arr+=("gcc");
depend_arr+=("crontab");
depend_arr+=("npm");
depend_arr+=("xz-utils");
depend_arr+=("apache2");
depend_arr+=("php");
depend_arr+=("php-curl");

depend_needed=;
for depend_item in "${depend_arr[@]}"; do
	if ! type $depend_item &> /dev/null2>&1; then
		depend_needed="$depend_needed $depend_item";
	fi
done

#Install dependencies
echo "will verify install of:$depend_needed";
while true; do
	read -p "Are you ok with installing these now? (you must to continue with install) " yn
	case $yn in
		[Yy]* )
			break;;
		[Nn]* )
			printf "We cannot proceed without these installed.";
			exit;;
		* ) echo "Please answer yes[Y] or no[N].\n";;
	esac
done
apt install --force-yes --yes $depend_needed
printf "\n\nBase Dependencies Installed!\n";

function install_node () {
	url="https://deb.nodesource.com/setup_6.x";
	curl -sL $url | sudo -E bash -
	apt install --force-yes --yes nodejs
}

#check/install node version
printf "Checking if Node JS is installed\n";
if ! type node &> /dev/null2>&1; then
	while true; do
		read -p "Node JS is not installed. Install now?" yn
		case $yn in
			[Yy]* ) break;;
			[Nn]* )
				printf "\nUnfortunately, Node JS is required for the web control to function.\n\n";
				exit;;
			* ) echo "Please answer yes[Y] or no[N].";;
		esac
	done
	install_node;
else
	version=`node -v`;
	supported_node="6.9.5";
	if version_gt $supported_node $version; then
		printf "Only node $supported_node and above is supported.\nYou currently have $version installed\n";
		while true; do
			read -p "Attempt to update now?" yn
			case $yn in
				[Yy]* ) break;;
				[Nn]* )
					printf "\nPlease manually update your node JS then attempt install again.\n\n";
					exit;;
				* ) echo "Please answer yes[Y] or no[N].";;
			esac
		done
		install_node;
	fi
fi
if ! type node &> /dev/null2>&1; then
	printf "for some reason, Node JS was unable to install. Please manually insatll node js version 6.9.5 or greater, ensure that it is installed with \`which node\`, and run this install script again";
	exit;
fi
version=`node -v`;
printf "Node JS $version is installed\n\n";

#Factorio Install
if [ ! -d "$install_dir/" ]; then
	printf "Factorio is not installed.\nAttempting to identify latest stable version...\n";
	latest_version=`curl -v --silent https://updater.factorio.com/get-available-versions 2>&1 | grep stable | awk '{ print $2 }' | tr -d '"'`;
	if [ "${latest_version}" ]; then
		printf "Latest stable Factorio version is $latest_version. ";
		while true; do
			read -p "Download the latest version?  " yn
			case $yn in
					[Yy]* )
						mkdir $install_dir
						download=`curl -JLO# https://www.factorio.com/get-download/$latest_version/headless/linux64`
						download=`echo $download | awk '{ print $5 }' | tr -d "'"`
						if [ "${download}" ]; then
							printf "Downloaded to $download\n"
							printf "extracting to $install_dir/$latest_version/ ... "
							mkdir $install_dir/$latest_version
							extract $download $install_dir $latest_version
							chown -R www-data:www-data $install_dir/
						else
							printf "Unable to download latest version. Don't worry. We can install this later\n"
						fi
						break;;
					[Nn]* ) printf "That's alright. We can download it later.\n"; break;;
					* ) echo "Please answer yes[Y] or no[N].";;
			esac
		done
	else
		printf "Unable to download latest version. Don't worry. We can install this later\n"
	fi
fi

#DEV carry on from here

echo "Downloading latest version of Web Control\n"
wget -q https://github.com/3RaGaming/Web_Control/archive/master.zip -O /tmp/master.zip
echo "Unzipping\n"
unzip -u /tmp/master.zip -d /tmp/
echo "Creating directories\n"
mkdir -p /var/www/
echo "Installing Web Control\n"
cp -R /tmp/Web_Control-master/* /var/www/
echo "Adjusting permissions\n"
chown -R www-data:www-data /var/www/
chmod +x /var/www/factorio/manage.sh
chmod +x /var/www/html/update.sh
echo "Activating cron job for permissions\n"
crontab /var/www/cronjob.txt
echo "Compiling managepgm\n"
cd /var/www/factorio/
printf "\nPreparing to compile the manager, and install discord js.\n"
printf "Some warning messages will appear. These are normal. They are due to using a discord plugin that is capable of using voice, but we have no bot voice scripts implemented.\n"
printf "\nPress Enter to continue.\n"
read
gcc -o managepgm -pthread manage.c
echo "Installing Discord.js\n"
npm install discord.js --save
echo "Cleaning temporary files\n"
rm -Rf /tmp/master.zip /tmp/Web_Control-master/
echo "Installation complete. Press enter to exit.\n"
read

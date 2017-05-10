#!/bin/bash
if [ "$EUID" -ne 0 ]
        then printf "Please run as root\n"
        exit
fi

install_dir="/usr/share/factorio"
#install_dir="/root/test"

#compressed file extraction function. 0.14 is in tar.gz, and .15 is in tar.xz, for some reason.
extract () {
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

printf "Welcome to 3Ra Gaming's Factorio Web Control Installer!\n\n"
#printf "This tool will automatically check that all required dependancies are installed\n"
#printf "If any are not yet installed, it will attempt to install them.\n\n"
printf "This script DOES NOT yet verify all dependancies. Please check the github to ensure you have all the prerequisites installed\n"
while true; do
        read -p "Are you currently running Ubuntu 16.04 or higher?  " yn
        case $yn in
                [Yy]* ) break;;
                [Nn]* )
                        printf "\n\nUnfortunately, this installer was built for Ubuntu :(\n\n";
                        printf "Please consult the github for manual instructions\n";
                        printf "http://www.3ragaming.com/github\n";
                        printf "You may also join our Discord and we will do our best to assist you\n";
                        printf "http://www.3ragaming.com/discord\n\n";
                        exit;;
                * ) echo "Please answer yes[Y] or no[N].";;
        esac
done

#this statement is for debug testing. This should be removed when released to production
if [ "$install_dir" == "/root/test" ]; then
	if [ -d "$install_dir/" ]; then
		rm -Rf $install_dir
	fi
fi

#Factorio Install
if [ ! -d "$install_dir/" ]; then
	printf "Factorio is not installed. Attempting to identify latest stable version...\n"
	latest_version=`curl -v --silent https://updater.factorio.com/get-available-versions 2>&1 | grep stable | awk '{ print $2 }' | tr -d '"'`;
	if [ "${latest_version}" ]; then
		printf "latest version is $latest_version. ";
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
gcc -o managepgm -pthread manage.c
echo "Installing Discord.js\n"
npm install discord.js --save
echo "Cleaning temporary files\n"
rm -Rf /tmp/master.zip /tmp/Web_Control-master/
echo "Installation complete. Press enter to exit.\n"
read

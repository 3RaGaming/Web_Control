#!/bin/bash
if [ "$EUID" -ne 0 ]
	then printf "Please run as root\n"
	exit
fi

#set silent install flag and standard user/password
case $1 in
	'-s'|'--silent') silent=1; silent_user=admin; silent_password=password;;
	*)               silent=0;;
esac

install_dir="/usr/share/factorio"
supported_node="6.9.5";

#compressed file extraction function. 0.14 is in tar.gz, and .15 is in tar.xz, for some reason.
function extract_f () {
	if [ -f $1 ] ; then
		case $1 in
			*.tar.gz)  tar --strip-components=1 -xzf $1 -C $2/$3; printf "Done!\n";;
			*.tar.xz)  tar --strip-components=1 -xf $1 -C $2/$3; printf "Done!\n";;
			*)         printf "Unknown compression type can't extract from $1\n"; fail_fac_install=true; break;;
		esac
	else
		printf "Unable to access file $1... We'll deal with that later...\n"
		fail_fac_install=true;
	fi
}

#version checker. Will need this in the case node is already installed
function version_gt () {
	test "$(printf '%s\n' "$@" | sort -V | head -n 1)" != "$1";
}

#factorio installation. $1 = install_dir $2 = latest_version
function install_factorio () {
	mkdir $1
	download=`curl -JLO# https://www.factorio.com/get-download/$2/headless/linux64`
	download=`echo $download | awk '{ print $5 }' | tr -d "'"`
	if [ "${download}" ]; then
		printf "Downloaded to $download\n"
		printf "extracting to $1/$2/ ... "
		mkdir $1/$2
		extract_f $download $1 $2
		chown -R www-data:www-data $1/
	else
		printf "Unable to download latest version. Don't worry. We can install this later\n"
	fi
}

function install_node () {
	url="https://deb.nodesource.com/setup_6.x";
	curl -sL $url | sudo -E bash -
	apt install --force-yes --yes nodejs
}

function set_username(){
    printf "Please enter a username to create:\n"
    read username
    if [[ -n "$username" ]]; then
        a=$(echo $username | tr -d "\n" | wc -c)
        b=$(echo $username | tr -cd "[:alnum:]" | wc -c)
        if [[ $a != $b ]]; then
            printf "Only letters and numbers are supported for usernames.\n"
            set_username
        else
			g_username=$(echo $username | tr -cd "[:alnum:]")
        fi
    else
        printf "Username cannot be left empty.\n"
        set_username
    fi
}

function set_password(){
    printf "Enter a password:\n"
    read -s password_1
    if [[ -n "$password_1" ]]; then
        a=$(echo -n $password_1 | md5sum | awk '{ print $1 }')
		printf "Re-enter password:\n"
		read -s password_2
		if [[ -n "$password_2" ]]; then
			b=$(echo -n $password_2 | md5sum | awk '{ print $1 }')
			if [[ $a != $b ]]; then
				printf "Passwords do not match.\n"
				set_password
			else
				g_password=$b
			fi
		else
			printf "Passwords do not match.\n"
			set_password
		fi
    else
        printf "Password cannot be left empty.\n"
        set_password
    fi
}

printf "Welcome to 3Ra Gaming's Factorio Web Control Installer!\n\n"
#printf "This tool will automatically check that all required dependancies are installed\n"
#printf "If any are not yet installed, it will attempt to install them.\n\n"
printf "This script should verify all dependancies and will attempt to install them.\n"
while [ $silent == 0 ]; do
	read -p "Are you currently running Ubuntu 16.04 or higher? [y/n] " yn
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
depend_arr+=("rsync");
depend_arr+=("gcc");
depend_arr+=("cron");
depend_arr+=("wget");
depend_arr+=("screen");
depend_arr+=("sudo");
depend_arr+=("npm");
depend_arr+=("xz-utils");
depend_arr+=("apache2");
depend_arr+=("php");
depend_arr+=("php-curl");
depend_arr+=("libapache2-mod-php");

depend_needed=;
for depend_item in "${depend_arr[@]}"; do
	if ! type $depend_item &> /dev/null2>&1; then
		apt install --force-yes --yes $depend_item
	fi
done

#Install dependencies
#printf "will verify install of:$depend_needed\n";
#apt install --force-yes --yes $depend_needed
printf "Base Dependencies Installed!\n\n";

#check/install node version
printf "Checking if Node JS is installed\n";
if ! type node &> /dev/null2>&1; then
	printf "Node JS is not installed. Installing.../n";
	install_node;
else
	version=`node -v`;
	if version_gt $supported_node $version; then
		printf "Only node $supported_node and above is supported.\nYou currently have $version installed\n";
		while [ $silent == 0 ]; do
			read -p "Attempt to update now? [y/n] " yn
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
	printf "for some reason, Node JS was unable to install. Please manually insatll node js version 6.9.5 or greater, ensure that it is installed with \`which node\`, and run this install script again\n";
	exit;
fi
version=`node -v`;
printf "Node JS $version is installed\n\n";

#Factorio Install
if [ ! -d "$install_dir/" ]; then
	printf "Factorio is not installed.\nAttempting to identify latest stable version...\n";
	latest_version=`curl -v --silent https://updater.factorio.com/get-available-versions 2>&1 | grep stable | awk '{ print $2 }' | tr -d '"'`;
	if [ "${latest_version}" ]; then
		printf "Latest stable Factorio version is $latest_version. Installing...\n";
		install_factorio $install_dir $latest_version
		if [ -d "$install_dir/" ]; then
			printf "Factorio $latest_version installed!\n\n";
		else
			printf "Unable to download latest version. Don't worry. We can install this later\n";
			fail_fac_install=true;
		fi
	else
		printf "Unable to download latest version. Don't worry. We can install this later\n";
		fail_fac_install=true;
	fi
fi

printf "Downloading latest version of Web Control\n";
wget -q https://github.com/3RaGaming/Web_Control/archive/master.zip -O /tmp/master.zip
printf "Unzipping\n";
unzip -u /tmp/master.zip -d /tmp/
printf "Creating directories\n";
mkdir -p /var/www/
printf "Installing Web Control\n";
rsync --ignore-existing -a -v /tmp/Web_Control-master/* /var/www/
printf "Adjusting permissions\n";
chown -R www-data:www-data /var/www/
chmod +x /var/www/factorio/manage.sh
chmod +x /var/www/html/update.sh

config_file="/var/www/factorio/server1/config/config.ini";
if [ ! -d "/var/www/factorio/server1" ]; then
	printf "\"Server1\" not found. Renaming example folder.\n";
	mv /var/www/factorio/serverexample /var/www/factorio/server1
	#need to fix read data and save data also
	if [ -z "$fail_fac_install" ]; then
		printf = "Please be sure to insatll a factorio version using the web control before attempting to start a game server";
	else
		dir="/usr/share/factorio/*";
		for file in $dir; do
			latest_dir=`echo "$file" | awk -F "/" '{ print $5 }'`
			break
		done
		if [ -z "$latest_dir" ]; then
			read_data=`grep "read-data" $config_file`;
			read_data_new="read-data=/usr/share/factorio/$latest_dir";
			sed -i -e "s|$read_data|$read_data_new|g" "$config_file"
			printf "Updated: $read_data_new\n";	
		else
			printf = "Error setting read-data. Please use the web control to save a server config before attempting to start the server.";
		fi
	fi
fi

#ensure write-data is set correctly
save_data=`grep "write-data" $config_file`;
#change it to
save_data_new="write-data=/var/www/factorio/server1";
sed -i -e "s|$save_data|$save_data_new|g" "$config_file"
printf "Updated: $save_data_new\n";

printf "We need to install a cronjob for managing deleting old file logs and checking file permissions periodically.\n";
printf "We will save your current cronjob file as \"cronjob_old.txt\" in case you need to add anything custom back to it\n";
printf "Press Enter when ready.\r";
if [ $silent == 0 ]; then
	read
fi
printf "Activating cron job for permissions\n";
crontab -l > cronjob_old.txt
crontab /var/www/cronjob.txt
printf "Compiling managepgm\n";
cd /var/www/factorio/
printf "\nPreparing to compile the manager, and install discord js.\n";
printf "Some warning messages about discord will appear. These are normal, you may ignore them.\n";
printf "\nPress Enter to continue.\n";
if [ $silent == 0 ]; then
	read
fi
gcc -o managepgm -pthread manage.c
printf "Installing Discord.js\n"
npm install discord.js --save
printf "Cleaning temporary files\n"
rm -Rf /tmp/master.zip /tmp/Web_Control-master/
printf "Enabling SSL and restarting web server\n";
a2enmod ssl
a2ensite default-ssl
printf "Checking upload limits\n";
php_ini=`php --ini | grep Loaded | awk '{ print $4 }'`
if [ -f "$php_ini" ]
then
	echo "$php_ini found."
	php_ini_post_max_size_raw=`grep post_max_size $php_ini`
	php_ini_post_max_size=`grep post_max_size $php_ini | awk '{ print $3 }' | tr -dc '0-9'`
	php_ini_upload_max_filesize_raw=`grep upload_max_filesize $php_ini`
	php_ini_upload_max_filesize=`grep upload_max_filesize $php_ini | awk '{ print $3 }' | tr -dc '0-9'`
	if [ "$php_ini_post_max_size" -lt "156" ]; then
		#change it to
		php_ini_post_max_size_raw=`grep post_max_size $php_ini`
		php_ini_most_max_size_new="post_max_size = 156M";
		sed -i -e "s/$php_ini_post_max_size_raw/$php_ini_most_max_size_new/g" "$php_ini"
		printf "Updated post_max_size to 156M\n";
	else
		printf "$php_ini_post_max_size_raw, this will do\n";
	fi
	if [ "$php_ini_upload_max_filesize" -lt "150" ]; then
		#change it
		php_ini_upload_max_filesize_raw=`grep upload_max_filesize $php_ini`
		php_ini_upload_max_filesize_new="upload_max_filesize = 150M";
		sed -i -e "s/$php_ini_upload_max_filesize_raw/$php_ini_upload_max_filesize_new/g" "$php_ini"
		printf "Updated upload_max_filesize to 150M\n";
	else
		printf "$php_ini_upload_max_filesize_raw, this will do\n";
	fi
else
	printf "Unable to location php_ini file.\nYou will be required to change the 'post_max_size' and 'upload_max_filesize' in your php.ini file.";
fi
service apache2 restart

#request to remove index.html
file="/var/www/html/index.html";
if [ -f "$file" ]; then
	printf "Ubuntu likes to install a default web file at $file\n";
	printf "This file is un-needed and will make using the web control difficult.";
	if [ $silent == 0 ]; then
		while true; do
			read -p "Should we remove this file now? [y/n] " yn
			case $yn in
					[Yy]* )
						rm -f $file
						printf "File $file removed.\n";
						break;;
					[Nn]* )
						printf "If you have made this file yourself, please rename it (anything but index) so the web control may function properly.\n";
						printf "Press Enter to continue...";
						read
						break;;
					* ) echo "Please answer yes[Y] or no[N].";;
			esac
		done
	else
		rm -f $file
		printf "File $file removed.\n";
	fi
fi

printf "Installation complete!\n\n";
printf "We will need to setup a user for you to login without discord authentication for now.\n";

while [ $silent == 0 ]; do
	read -p "Would you like to setup this user now? (This will remove all other users from the users.txt file) [y/n] " yn
	case $yn in
			[Yy]* )
				set_username
				set_password
				printf "Will create user \"$g_username\" with password $g_password\n";
				echo "$g_username|$g_password|admin" > /var/www/users.txt;
				break;;
			[Nn]* ) printf "If you find you cannot login using /altlogin.php, edit the /var/www/users.txt file.\n"; break;;
			* ) echo "Please answer yes[Y] or no[N].";;
	esac
done

#create standard login for silent installation
if [ $silent == 1 ]; then
        a=$(echo -n $silent_password | md5sum | awk '{ print $1 }')
	echo "$silent_user|$a|admin" > /var/www/users.txt;
fi

printf "Additional users may be added using additional lines in /var/www/users.txt. Passwords are MD5 encrypted\n";
printf "Access your site with https://IP_ADDRESS/altlogin.php\n";
printf "Eventually we will have a splash page for first time logins to assit the rest of the web control setup.\n";
printf "Until then, the rest of the configuration must be done manually in /var/www/factorio/config.json\n";
printf "Press Enter to exit.\n";
if [ $silent == 0 ]; then
	read
fi



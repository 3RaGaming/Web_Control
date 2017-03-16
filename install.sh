echo "Please insure you have all the dependencies installed as described on the GitHub README\n"
if [ "$EUID" -ne 0 ]
	then echo "Please run as root"
	exit
fi
echo "Press Enter to continue\n"
read
echo "Checking for Factorio install\n"
if [ ! -d "/usr/share/factorio/" ]; then
	echo "Factorio not installed. Checking for factorio.tar.gz\n"
	if [ ! -f "factorio.tar.gz" ]; then
		echo "Neither Factorio nor factorio.tar.gz found. The installer cannot continue.\n"
		echo "Press Enter to exit\n"
		read
		exit
	else
		echo "Installing Factorio\n"
		mkdir -p /usr/share/factorio/
		tar -xvzf /tmp/
		cp -R /tmp/factorio/* /usr/share/factorio/
		chown -R www-data:www-data /usr/share/factorio/
		rm -Rf /tmp/factorio/
	fi
fi
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

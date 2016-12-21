#!/bin/bash
args=("$@");

if [ "${args[0]}" ]; then
	case "${args[0]}" in
	    'count')
			echo "5"
            exit 1
            ;;

        '1')
            printf "Step ${args[0]} - Downloading latest updates \r\n";
            wget -q https://github.com/3RaGaming/Web_Control/archive/master.zip -O /tmp/master.zip
            printf "\r\n-----------\r\n\r\n";
            ;;

        '2')
            printf "Step ${args[0]} - Unzipping updates \r\n";
            unzip -u /tmp/master.zip -d /tmp/
            printf "\r\n-----------\r\n\r\n";
            ;;

        '3')
            printf "Step ${args[0]} - Updating files \r\n";
            rsync -a -v /tmp/Web_Control-master/html/* ./
            rsync -a -v /tmp/Web_Control-master/factorio/manage.c /var/www/factorio/
            rsync -a -v /tmp/Web_Control-master/factorio/manage.sh /var/www/factorio/
            rsync -a -v /tmp/Web_Control-master/factorio/3RaFactorioBot.js /var/www/factorio/
            printf "\r\n-----------\r\n\r\n";
            ;;

        '4')
            printf "Step ${args[0]} - Compiling updated manage.c \r\n";
            gcc -o /var/www/factorio/managepgm -pthread /var/www/factorio/manage.c
            printf "\r\n-----------\r\n\r\n";
            ;;

        '5')
            printf "Step ${args[0]} - Deleting temporary files \r\n";
            rm -Rf /tmp/master.zip /tmp/Web_Control-master/
            printf "\r\n-----------\r\n\r\n";
            ;;

        *)
            printf "Error in input provided\r\n"
            exit 1
	esac
else
    printf "No input provided\r\n"
fi

#!/bin/bash
result=("${PWD##*/}");
#use master repo by default. If subfolder is a branch that exists, we'll use that branch repo instead (example: beta)
if [ "$result" == "html" ]; then
	result="master";
fi
#args contains which number php is commanding to work on
#we do this in steps in hopes that php can flush it's output, so we cna view each step taking place.
args=("$@");
tmp_dir="/tmp";

if [ "${args[0]}" ]; then
	case "${args[0]}" in
		'count')
			#ensure total number of steps is echoed here! PHP uses this to know how many times to loop		
			echo "7"
			exit 1
			;;

		'1')
			printf "Detected \"$result\" branch \r\n";
			printf "Step ${args[0]} - Downloading latest updates \r\n";
			;;
		#DO NOT CHANGE THIS STEP
		#unless... you want to change update_web_control.php also
		'2')
			#wget -q https://github.com/3RaGaming/Web_Control/archive/$result.zip -O $tmp_dir/$result.zip
			wget -t 1 -T 5 https://github.com/3RaGaming/Web_Control/archive/$result.zip -O $tmp_dir/$result.zip && wget_result=true || wget_result=false
			#wget -t 1 -T 5 https://gitlab.com/3RaGaming/Web_Control/repository/archive.zip?ref=$result -O $tmp_dir/$result.zip && wget_result=true || wget_result=false
			if [ "$wget_result" = true ]; then
					printf "Download Completed \r\n"
			else
				download=$(cd $tmp_dir && { curl -JLO# https://gitlab.com/3RaGaming/Web_Control/repository/archive.zip?ref=$result ; cd -; })
				download=$(echo $download | awk '{ print $5 }' | tr -d "'")
				if [ "${download}" ]; then
						echo "$download"
				else
						echo "failed"
				fi
			fi
			;;

		'3')
			if [ "${args[1]}" ]; then
				printf "Step ${args[0]} - Unzipping updates \r\n";
				unzip -u $tmp_dir/${args[1]}.zip -d $tmp_dir/
			else
				printf "Step ${args[0]} - SKIPPED - Unzipping updates \r\n";
			fi
			printf "\r\n-----------\r\n\r\n";
			;;

		'4')
			printf "Step ${args[0]} - Compiling updated manage.c \r\n";
			gcc -o /var/www/factorio/managepgm -std=gnu99 -pthread /var/www/factorio/manage.c
			printf "\r\n-----------\r\n\r\n";
			;;

		'5')
			if [ "${args[1]}" ]; then
				printf "Step ${args[0]} - Compiling updated manage.c \r\n";
				gcc -o /var/www/factorio/managepgm -pthread /var/www/factorio/manage.c
			else
				printf "Step ${args[0]} - SKIPPED - Compiling updated manage.c \r\n";
			fi
			printf "\r\n-----------\r\n\r\n";
			;;

		'6')
			if [ "${args[1]}" ]; then
				printf "Step ${args[0]} - Deleting temporary files \r\n";
				rm -Rf $tmp_dir/${args[1]}.zip $tmp_dir/${args[1]}/
			else
				printf "Step ${args[0]} - SKIPPED - Deleting temporary files \r\n";
			fi
			printf "\r\n-----------\r\n\r\n";
			;;

		'7')
			if [ "${args[1]}" ]; then
			printf "Step ${args[0]} - forcing file permissions to www-data user \r\n";
			sudo chown -R www-data:www-data /var/www/
			sudo chown -R www-data:www-data /usr/share/factorio/
			else
				printf "Step ${args[0]} - SKIPPED - forcing file permissions to www-data user \r\n";
			fi
			printf "\r\n-----------\r\n\r\n";
			;;

		*)
			printf "Error in input provided\r\n"
			exit 1
	esac
else
	printf "No input provided\r\n"
fi

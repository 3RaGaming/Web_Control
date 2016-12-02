#!/bin/bash
args=("$@");

if [ "${args[0]}" ]; then
	case "${args[0]}" in
	    'count')
			echo "4"
            ;;

        '1')
            wget -q https://github.com/3RaGaming/Web_Control/archive/dev-bot-manage.zip -O dev-bot-manage.zip
            printf "4\r\n";
            ;;

        '2')
            unzip -u dev-bot-manage.zip
            printf "4\r\n";
            ;;

        '3')
            rsync -a -v Web_Control-dev-bot-manage/html/* ./
            printf "4\r\n";
            ;;

        '4')
            rm -Rf dev-bot-manage.zip Web_Control-dev-bot-manage/
            printf "4\r\n";
            ;;

        *)
            printf "Error in input provided\r\n"
            exit 1
	esac
else
    printf "No input provided\r\n"
fi

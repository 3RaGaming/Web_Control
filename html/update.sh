#!/bin/bash
args=("$@");

if [ "${args[0]}" ]; then
	case "${args[0]}" in
	    'count')
			echo "4"
            exit 1
            ;;

        '1')
            printf "Step ${args[0]} \r\n";
            wget -q https://github.com/3RaGaming/Web_Control/archive/dev-bot-manage.zip -O /tmp/dev-bot-manage.zip
            ;;

        '2')
            printf "Step ${args[0]} \r\n";
            unzip -u /tmp/dev-bot-manage.zip
            ;;

        '3')
            printf "Step ${args[0]} \r\n";
            rsync -a -v /tmp/Web_Control-dev-bot-manage/html/* ./
            ;;

        '4')
            printf "Step ${args[0]} \r\n";
            rm -Rf /tmp/dev-bot-manage.zip /tmp/Web_Control-dev-bot-manage/
            ;;

        *)
            printf "Error in input provided\r\n"
            exit 1
	esac
else
    printf "No input provided\r\n"
fi


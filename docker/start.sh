#!/bin/bash

service nginx start
service php7.4-fpm start
service cron start
echo STARTING
tail -F /var/log/nginx/all.log


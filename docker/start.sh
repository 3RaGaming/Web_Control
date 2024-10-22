#!/bin/bash

service nginx start
service php8.3-fpm start
service cron start
echo STARTING
tail -F /var/log/nginx/all.log


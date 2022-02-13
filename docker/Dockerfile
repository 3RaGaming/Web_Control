FROM ubuntu
#
# apt updates
RUN apt update
RUN apt upgrade -y
RUN DEBIAN_FRONTEND=noninteractive TZ="US/Mountain" apt-get -y install tzdata
RUN apt install -y sudo curl wget zip nano gcc tar xz-utils screen cron nginx php7.4-fpm php-curl php-json
#
# copy files
COPY nginx_default /etc/nginx/sites-enabled/default
COPY start.sh /tmp/start.sh
COPY cronjob.txt /tmp/cronjob.txt
COPY php.ini /etc/php/7.4/fpm/php.ini
RUN touch /var/log/nginx/all/log
RUN chmod +x /tmp/start.sh
#
# create folders
RUN rm -Rf /var/www/html
RUN mkdir -p /var/www/html
RUN mkdir -p /usr/share/factorio
#
# install node
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.1/install.sh | bash
RUN export NVM_DIR="$HOME/.nvm"
RUN nvm install 16.14.0
RUN npm install -g npm@8.5.0
#
# download, extract and install repo
RUN wget https://gitlab.com/3RaGaming/Web_Control/-/archive/master/Web_Control-master.zip -O /tmp/master.zip
RUN unzip /tmp/master.zip -d /tmp/
RUN mv /tmp/Web_Control-master/* /var/www/
RUN rm -Rf /tmp/master.zip /tmp/Web_Control-master/
RUN mv /var/www/factorio/serverexample /var/www/factorio/server1
RUN cd /var/www/factorio && gcc -o managepgm -std=gnu99 -pthread manage.c
RUN cd /var/www/factorio && npm i --save --no-optional discord.js
#
# set file permissions
RUN chown -R www-data:www-data /usr/share/factorio /var/www/
#
# Other required tasks
RUN crontab /tmp/cronjob.txt
#
# set command to start the container
CMD /tmp/start.sh

FROM ubuntu
EXPOSE 8080/tcp
EXPOSE 34291/udp
EXPOSE 34292/udp
EXPOSE 34293/udp
EXPOSE 34294/udp
EXPOSE 34295/udp
EXPOSE 34296/udp
EXPOSE 34297/udp
EXPOSE 34298/udp
EXPOSE 34299/udp
#
# apt updates
RUN apt update
RUN apt upgrade -y
RUN DEBIAN_FRONTEND=noninteractive TZ="US/Mountain" apt-get -y install tzdata
RUN apt install -y sudo apt-utils curl wget zip nano gcc tar xz-utils screen cron nginx php7.4-fpm php-curl php-json php-zip
#
# install node
RUN curl -sL https://deb.nodesource.com/setup_16.x -o /tmp/nodesource_setup.sh
RUN bash /tmp/nodesource_setup.sh
RUN apt install -y nodejs
RUN npm install -g npm@8.5.0
#
# copy files
COPY nginx_default /etc/nginx/sites-enabled/default
COPY start.sh /tmp/start.sh
COPY cronjob.txt /tmp/cronjob.txt
COPY php.ini /etc/php/7.4/fpm/php.ini
COPY sudoers /etc/sudoers
RUN chmod +x /tmp/start.sh
#
# create folders
RUN rm -Rf /var/www/html
RUN mkdir -p /var/www/html
RUN mkdir -p /usr/share/factorio
RUN mkdir -p /var/log/nginx
RUN touch /var/log/nginx/all.log
#
# download, extract and install repo
RUN wget https://gitlab.com/3RaGaming/Web_Control/-/archive/master/Web_Control-master.zip -O /tmp/master.zip
RUN unzip /tmp/master.zip -d /tmp/
RUN mv /tmp/Web_Control-master/* /var/www/
RUN rm -Rf /tmp/master.zip /tmp/Web_Control-master/
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

FROM ubuntu
RUN apt update
RUN apt upgrade -y
RUN DEBIAN_FRONTEND=noninteractive TZ=US/Mountain apt-get -y install tzdata
RUN apt install -y curl wget zip nginx php7.4-fpm nano gcc npm tar xz-utils screen
RUN apt install -y php-curl
COPY master.zip /tmp/master.zip
RUN rm -Rf /var/www/html
RUN mkdir -p /var/www/html
RUN unzip /tmp/master.zip -d /var/www/
RUN rm /tmp/master.zip
RUN mv /var/www/Web_Control-master/* /var/www/
RUN rm -Rf /var/www/Web_Control-master/
RUN mv /var/www/factorio/serverexample /var/www/factorio/server1
RUN mkdir -p /usr/share/factorio
RUN chown www-data:www-data /usr/share/factorio
RUN cd /var/www/factorio && gcc -o managepgm -std=gnu99 -pthread manage.c
RUN cd /var/www/factorio && npm i --save --no-optional discord.js
COPY nginx_default /etc/nginx/sites-enabled/default
COPY start.sh /tmp/start.sh
RUN chmod +x /tmp/start.sh
CMD /tmp/start.sh

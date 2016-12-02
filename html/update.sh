#!/bin/bash

wget -q https://github.com/3RaGaming/Web_Control/archive/dev-bot-manage.zip \
    -O dev-bot-manage.zip && unzip -u dev-bot-manage.zip && \
    rsync -a -v Web_Control-dev-bot-manage/html/* ./ \
    && rm -Rf dev-bot-manage.zip Web_Control-dev-bot-manage/
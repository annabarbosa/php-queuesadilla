FROM ubuntu:14.04

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get -qq update

RUN apt-get -qq install -qq -y software-properties-common python-software-properties curl
RUN add-apt-repository ppa:chris-lea/redis-server
RUN apt-get -qq update
RUN apt-get -qq install -qq -y beanstalkd
RUN apt-get -qq install -qq -y redis-server
RUN apt-get -qq install -qq -y mysql-server
RUN apt-get -qq install -qq -y postgresql postgresql-contrib
RUN apt-get -qq install -qq -y rabbitmq-server
RUN apt-get -qq install -qq -y make
RUN apt-get -qq install -qq -y git
RUN apt-get -qq install -qq -y zip

# `language-pack-en-base` is necessary to properly install the key for ppa:ondrej/php5
RUN \
    locale-gen en_US.UTF-8 && \
    apt-get -qq install -qq -y language-pack-en-base
RUN LC_ALL=en_US.UTF-8 add-apt-repository ppa:ondrej/php && \
    apt-get -qq update
RUN apt-get -qq install -qq -y php7.0-cli php7.0-curl php7.0-mysql php7.0-pgsql php-pear php7.0-xdebug php7.0-redis php7.0-xml

RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

ADD composer.json /app/composer.json
WORKDIR /app

RUN pear install --alldeps PHP_CodeSniffer > /dev/null

ENV BEANSTALK_URL="beanstalk://127.0.0.1:11300?queue=default&timeout=1" \
    MEMORY_URL="memory:///?queue=default&timeout=1" \
    MYSQL_URL="mysql://travis@127.0.0.1:3306/database_name?queue=default&timeout=1" \
    NULL_URL="null:///?queue=default&timeout=1" \
    RABBITMQ_URL="rabbitmq://guest:guest@127.0.0.1:5672/?queue=default&timeout=1" \
    REDIS_URL="redis://127.0.0.1:6379/0?queue=default&timeout=1" \
    MEMORY_URL="synchronous:///?queue=default&timeout=1" \
    POSTGRES_URL="pgsql://travis:asdf12@127.0.0.1:5432/database_name?queue=default"

VOLUME ["/app"]

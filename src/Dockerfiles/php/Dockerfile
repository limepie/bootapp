FROM debian:jessie

RUN apt-get update && apt-get install -y autoconf apt-utils git gcc wget re2c php5-dev php5-common php5-cli php5-fpm php5-mcrypt php5-mysqlnd php5-apcu php5-curl php5-intl

RUN git clone --depth=1 git://github.com/phalcon/cphalcon.git /usr/local/src/cphalcon
RUN cd /usr/local/src/cphalcon/build && ./install ;\
    echo "extension=phalcon.so" > /etc/php5/mods-available/phalcon.ini ;\
    php5enmod phalcon

RUN apt-get install -y libyaml-dev ;\
    pecl install yaml ;\
    echo "extension=yaml.so" > /etc/php5/mods-available/yaml.ini ;\
    php5enmod yaml

RUN apt-get install -y librabbitmq-dev ;\
    pecl install amqp ;\
    echo "extension=amqp.so" > /etc/php5/mods-available/amqp.ini ;\
    php5enmod amqp

RUN apt-get install -y libmemcached-dev php5-memcached ;\
    php5enmod memcached

RUN pecl install redis ;\
    echo "extension=redis.so" > /etc/php5/mods-available/redis.ini ;\
    php5enmod redis


# Install composer
RUN bash -c "wget http://getcomposer.org/composer.phar && chmod +x composer.phar && mv composer.phar /usr/local/bin/composer"

# Install PHPUnit
RUN bash -c "wget https://phar.phpunit.de/phpunit.phar && chmod +x phpunit.phar && mv phpunit.phar /usr/local/bin/phpunit"

ADD bootapp.ini /etc/php5/fpm/conf.d/
ADD bootapp.ini /etc/php5/cli/conf.d/
ADD bootapp.pool.conf /etc/php5/fpm/pool.d/

RUN usermod -u 1000 www-data

CMD ["php5-fpm", "-F"]

EXPOSE 9000

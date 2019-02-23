FROM php:7.1-jessie

RUN ln -sf /bin/bash /bin/sh && apt-get update && apt-get install -y git python3 unzip locales --no-install-recommends \
    && printf "zh_CN.UTF-8 UTF-8\nen_US.UTF-8 UTF-8" >> /etc/locale.gen && locale-gen \
    && curl -sS -C - -L https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

# ENV LANG zh_CN.UTF-8

RUN git clone https://github.com/JakeWorrell/docodile.git /usr/local/src/docodile && pushd /usr/local/src/docodile && composer install

WORKDIR /usr/local/src/docodile

################################################################
# Data Factory - Direction des données - Institut Curie        #
# FRANCE - Paris                                               #
# Container for Health Data Metrics                            #
################################################################

ARG PHP_VERSION=7.4.15

FROM php:${PHP_VERSION}-apache

ARG APP_VERSION=2.2.0

# hadolint ignore=DL3008
RUN apt-get update && \
    apt-get -y --no-install-recommends install wget unzip libldap2-dev zip libzip-dev openssl libc-dev build-essential default-libmysqlclient-dev msmtp msmtp-mta && \
    rm -R /var/lib/apt/lists/*
    
# Configuring LDAP
RUN docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu
RUN docker-php-ext-install ldap

# Configuring ZIP
RUN docker-php-ext-configure zip
RUN docker-php-ext-install zip

# Configuring PDO/MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Copy VHost
COPY hdm.conf /etc/apache2/sites-enabled/hdm.conf

# Copy SMTP config
COPY msmtprc /etc/msmtprc

WORKDIR /var/www/html/

# Get Sources
COPY . /var/www/html/

# Add Version number
RUN rm -rf /var/www/html/version && \
    mkdir /var/www/html/version && \
	touch /var/www/html/version/version.json && \
	echo '{	"version":"${APP_VERSION}" }' >> /var/www/html/version/version.json

# Installing composer
SHELL ["/bin/bash", "-o", "pipefail", "-c"]
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installing php App Dependencies
RUN composer install --no-dev --optimize-autoloader 

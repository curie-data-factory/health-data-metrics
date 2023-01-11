################################################################
# Data Factory - Direction des données - Institut Curie        #
# FRANCE - Paris                                               #
# Container for Health Data Metrics                            #
################################################################

ARG PHP_VERSION=8.0.0

FROM php:${PHP_VERSION}-apache

ARG APP_VERSION=2.2.0

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# hadolint ignore=DL3008
RUN apt-get update && \
    apt-get -y --no-install-recommends install wget unzip libldap2-dev zip libzip-dev openssl libc-dev build-essential default-libmysqlclient-dev msmtp msmtp-mta && \
    rm -R /var/lib/apt/lists/*

RUN pear config-set http_proxy "$http_proxy" && \
    pear config-set php_ini "$PHP_INI_DIR/php.ini"


# Install XDebug
RUN pecl install redis \
    && pecl install xdebug

# Configuring LDAP
RUN docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu \
&&  docker-php-ext-install ldap

# Configuring ZIP
RUN docker-php-ext-configure zip \
&& docker-php-ext-install zip

# Configuring PDO/MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Copy VHost
COPY hdm.conf /etc/apache2/sites-enabled/hdm.conf

WORKDIR /var/www/html/

# Get Sources
COPY . /var/www/html/
RUN chmod u+x /var/www/html/start.sh

# Add Version number
RUN rm -rf /var/www/html/version && \
    mkdir /var/www/html/version && \
	touch /var/www/html/version/version.json && \
	echo '{	"version":"${APP_VERSION}" }' >> /var/www/html/version/version.json

# Installing composer
SHELL ["/bin/bash", "-o", "pipefail", "-c"]
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installing php App Dependencies
RUN composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-apache

# By default start up apache in the foreground, override with /bin/bash for interative.
CMD ["sh", "-c","/var/www/html/start.sh"]
#!/bin/bash
ln -sv /var/www/html/conf/mail/msmtprc /etc/msmtprc
/usr/sbin/apache2ctl -D FOREGROUND
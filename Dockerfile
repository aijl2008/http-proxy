FROM docker.artron.net/library/php:7.1-fpm-nginx
WORKDIR /var/www/public
ADD . .
ADD default.conf  /etc/nginx/sites-available/default
EXPOSE 80
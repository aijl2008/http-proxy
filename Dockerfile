FROM docker.artron.net/library/swoole:4.4.16-php7.4
WORKDIR /var/www/
ADD . .
RUN chown -R swoole:swoole /var/www/
USER swoole
ENTRYPOINT ["/usr/local/bin/php", "/var/www/swoole.php"]
EXPOSE 9501
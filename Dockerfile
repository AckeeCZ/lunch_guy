FROM ackee/composer-apache-base:php7-apache
MAINTAINER Marek Bartík <marek.bartik@ackee.cz>

ENV destdir /var/www/
WORKDIR $destdir
ENV runuser www-data

# copy and install app
COPY . $destdir
RUN composer --no-interaction install && \
    rm -rf $destdir/html && \
    ln -s $destdir/web $destdir/html && \
    chown -R $runuser:$runuser $destdir

FROM php:8.4-apache

RUN ["apt", "update"]
RUN ["apt-get", "install", "-y", "libzip-dev", "zip", "git"]

WORKDIR "/var/www/html/"
COPY ["composer.json", "composer.lock", "index.php", "afvalstoffendienst2ical.php", "."]
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN ["composer", "--no-dev", "install"]


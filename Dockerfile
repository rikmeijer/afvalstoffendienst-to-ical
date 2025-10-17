FROM php:8.4

RUN ["apt", "update"]
RUN ["apt-get", "install", "-y", "libzip-dev", "zip", "git"]

WORKDIR "/app"
COPY ["composer.json", "composer.lock", "afvalstoffendienst2ical.php", "."]
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN ["composer", "--no-dev", "install"]

CMD ["/usr/local/bin/php", "afvalstoffendienst2ical.php"]

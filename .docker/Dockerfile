FROM php:8.4-alpine AS build-stage

WORKDIR /app

COPY --from=composer /usr/bin/composer /usr/bin/composer
# copy application files
COPY . /app/

RUN composer install --no-dev --no-interaction --optimize-autoloader

# RUN node build
RUN apk add --no-cache nodejs npm
RUN npm install \
    && npm run build

RUN useradd -ms /bin/sh send-it
RUN chown -R send-it:send-it /app

FROM php:8.4-alpine

# Set environment to production
ENV APP_ENV=production
ENV APP_DEBUG=0

WORKDIR /app

RUN apk --no-cache add supervisor

COPY --from=build-stage /app /app

RUN php artisan octane:install --server=frankenphp --no-interaction

USER send-it
# copy runtime script
COPY .docker/start-container.sh /app/start-container
RUN chmod +x /app/start-container

ENTRYPOINT ["start-container"]

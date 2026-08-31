FROM nginx:alpine

WORKDIR /var/www/html

COPY public ./public

RUN rm -f ./public/storage \
    && mkdir -p ./public/storage

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

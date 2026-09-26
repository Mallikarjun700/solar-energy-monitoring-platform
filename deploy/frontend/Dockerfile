FROM node:22-alpine AS build

WORKDIR /app

COPY frontend/package*.json ./
RUN npm ci

COPY frontend/ ./
RUN npm run build -- --configuration production

FROM nginx:1.29-alpine

RUN apk add --no-cache gettext

COPY deploy/frontend/nginx.conf.template /etc/nginx/templates/default.conf.template
COPY deploy/frontend/entrypoint.sh /entrypoint.sh
COPY --from=build /app/dist/solar-energy-frontend/browser /usr/share/nginx/html

RUN chmod +x /entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["/entrypoint.sh"]

FROM nginx:alpine

COPY docker/nginx/prod.conf /etc/nginx/conf.d/default.conf
COPY backend/public /var/www/backend/public
COPY frontend/dist /usr/share/nginx/html

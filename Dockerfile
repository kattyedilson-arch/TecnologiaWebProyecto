FROM php:8.2-apache

# Instalar extensiones necesarias de PHP para MySQL (PDO)
RUN docker-php-ext-install pdo pdo_mysql

# Configuración explícita de OPcache (caché de bytecode)
COPY config/php/99-opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini

# Los errores de PHP se registran en el log en vez de mostrarse al usuario
COPY config/php/98-error-reporting.ini /usr/local/etc/php/conf.d/98-error-reporting.ini

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Establecer el directorio de trabajo
WORKDIR /var/www/html

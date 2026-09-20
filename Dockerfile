FROM php:8.2-apache

# Instalar extensiones necesarias de PHP para MySQL (PDO)
RUN docker-php-ext-install pdo pdo_mysql

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Establecer el directorio de trabajo
WORKDIR /var/www/html

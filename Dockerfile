FROM php:8.2-apache

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configurar Apache
RUN a2enmod rewrite
COPY . /var/www/html/
WORKDIR /var/www/html

# Instalar dependencias de Composer
RUN composer install

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html

# Exponer el puerto
EXPOSE 80

# Comando para iniciar Apache
CMD ["apache2-foreground"] 
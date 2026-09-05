# Official PHP 8.2 with Apache - Lightweight
FROM php:8.2-apache

# Enable Apache mod_rewrite (for .htaccess routing)
RUN a2enmod rewrite

# Allow .htaccess overrides in DocumentRoot
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copy all project files into Apache web root
COPY . /var/www/html/

# Set permissions for storage folder (JSON database files)
RUN mkdir -p /var/www/html/storage \
    && chmod -R 777 /var/www/html/storage

# Expose port 80
EXPOSE 80

# Official PHP 8.2 with Apache
FROM php:8.2-apache

# Enable Apache mod_rewrite (for .htaccess URL routing)
RUN a2enmod rewrite

# Allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copy all project files into Apache web root
COPY . /var/www/html/

# Create storage directory with proper permissions
RUN mkdir -p /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

FROM php:8.2-apache

# Aktifkan ekstensi PDO MySQL yang dipakai aplikasi ini
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Perbaikan bug "More than one MPM loaded": pastikan hanya mpm_prefork yang aktif
# (mod_php butuh mpm_prefork, tapi image dasar kadang ikut memuat mpm_event/worker juga)
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* \
    && a2enmod mpm_prefork

# Salin seluruh source code ke folder root Apache
COPY . /var/www/html/

# Pastikan folder assets/img bisa ditulisi (buat upload foto menu)
RUN chown -R www-data:www-data /var/www/html/assets/img \
    && chmod -R 775 /var/www/html/assets/img

# Railway menyuntik PORT lewat environment variable; arahkan Apache ke situ
ENV PORT=8080
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

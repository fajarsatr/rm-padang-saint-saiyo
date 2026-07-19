FROM php:8.2-apache

# Aktifkan ekstensi PDO MySQL yang dipakai aplikasi ini
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Salin seluruh source code ke folder root Apache
COPY . /var/www/html/

# Pastikan folder assets/img bisa ditulisi (buat upload foto menu)
RUN chown -R www-data:www-data /var/www/html/assets/img \
    && chmod -R 775 /var/www/html/assets/img

# Railway menyuntik PORT lewat environment variable; arahkan Apache ke situ
ENV PORT=8080
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

# Perbaikan bug "More than one MPM loaded": dijalankan tepat sebelum Apache mulai
# (bukan cuma saat build), supaya pasti hanya mpm_prefork yang aktif setiap kali
# container ini dijalankan ulang oleh Railway.
CMD ["bash", "-lc", "set -eux; a2dismod mpm_event mpm_worker || true; rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* || true; a2enmod mpm_prefork; apache2ctl -t; exec apache2-foreground"]

FROM php:8.2-fpm

# 1) Instala pacotes de sistema necessários (incluindo libonig-dev)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    zip \
    unzip \
  && docker-php-ext-install pdo_pgsql \
  && docker-php-ext-install mbstring zip exif pcntl bcmath \
  && docker-php-ext-enable pdo_pgsql

# 2) Define o diretório de trabalho dentro do container
WORKDIR /var/www/html

# 3) Copia todo o código do seu projeto Laravel para dentro do container
COPY . /var/www/html

# 4) Copia o Composer da imagem oficial para dentro do container
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

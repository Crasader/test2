# Dockerfile for php
FROM centos:centos7

# MAINTAINER will be deprecated in release: v1.13.0. LABEL should be used instead.
# See https://github.com/docker/docker/blob/master/docs/deprecated.md#maintainer-in-dockerfile
#LABEL authors="michael@rd5,jim@rd5,sliver@rd5"

ENV PHP_INI_DIR /usr/local/etc
ENV SOURCE_DIR /usr/local/src

ENV PHP_URL https://secure.php.net/get/php-7.0.9.tar.gz/from/this/mirror

# Choose a faster mirror
RUN sed -i 's/\(^mirrorlist.*\)$/\1\&cc=jp/g' /etc/yum.repos.d/CentOS-Base.repo
RUN yum install -y epel-release && \
    yum update -y && \
    yum install -y \
    autoconf \
    bison \
    curl-devel \
    file \
    gcc-c++ \
    git \
    glibc-headers \
    libmcrypt \
    libmcrypt-devel \
    libxml2-devel \
    make \
    openssl-devel \
    re2c \
    tar \
    unzip \
    wget && \
    yum clean all

# Build php with a newer icu version
# See https://github.com/symfony/symfony/issues/14259
RUN set -ex && \
    cd $SOURCE_DIR && \
    curl -LsS http://download.icu-project.org/files/icu4c/54.1/icu4c-54_1-src.tgz \
    | tar -zx && \
    cd icu/source && \
    ./configure && \
    make && \
    make install

# Build php
RUN set -ex && \
    cd $SOURCE_DIR && \
    curl -LsS $PHP_URL | tar -zx && \
    cd php-7.0.9 && \
    ./configure \
    --with-config-file-path=${PHP_INI_DIR} \
    --with-config-file-scan-dir=${PHP_INI_DIR}/conf.d \
    --with-curl \
    --with-fpm-user=nobody \
    --with-fpm-group=nobody \
    --with-gettext \
    --with-iconv \
    --with-libdir=lib64 \
    --with-libxml-dir=/usr/lib \
    --with-mcrypt \
    --with-openssl \
    --with-pdo-mysql \
    --with-zlib \
    --disable-ipv6 \
    --enable-bcmath \
    --enable-fpm \
    --enable-intl \
    --enable-mbstring \
    --enable-mysqlnd \
    --enable-sockets &&\
    make && \
    make install

# xdebug
RUN set -ex && \
    cd $SOURCE_DIR && \
    curl -LsS https://pecl.php.net/get/xdebug-2.4.0.tgz | tar zx && \
    cd xdebug-2.4.0 && \
    phpize && \
    ./configure && \
    make && \
    make install

# Clear source code
RUN cd $SOURCE_DIR && rm -rf *

RUN set -ex \
    && cd $PHP_INI_DIR \
    && sed 's!=NONE/!=!g' php-fpm.conf.default | tee php-fpm.conf > /dev/null \
    && cp php-fpm.d/www.conf.default php-fpm.d/www.conf \
    && { \
        echo '[global]'; \
        echo 'error_log = /proc/self/fd/2'; \
        echo; \
        echo '[www]'; \
        echo '; if we send this to /proc/self/fd/1, it never appears'; \
        echo 'access.log = /proc/self/fd/2'; \
        echo; \
        echo 'clear_env = no'; \
        echo; \
        echo '; Ensure worker stdout and stderr are sent to the main error log.'; \
        echo 'catch_workers_output = yes'; \
    } | tee php-fpm.d/docker.conf \
    && { \
        echo '[global]'; \
        echo 'daemonize = no'; \
        echo; \
        echo '[www]'; \
        echo 'listen = [::]:9000'; \
    } | tee php-fpm.d/zz-docker.conf

ENV PATH /usr/local/bin:$PATH
ENV PATH /var/www/html/bin:$PATH

# Copy our config files
COPY php.ini ${PHP_INI_DIR}
COPY *.conf ${PHP_INI_DIR}/php-fpm.d

RUN mkdir -p /var/www/html
WORKDIR /var/www/html

# Install composer
# See https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md
RUN curl -s https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

EXPOSE 9000
CMD ["php-fpm"]

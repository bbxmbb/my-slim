# Slim Framework 4 Skeleton Application - My Version

Use this skeleton application to quickly setup and start working on a new Slim Framework 4 application. This application uses the latest Slim 4 with Slim PSR-7 implementation and PHP-DI container implementation. It also uses the Monolog logger.

This skeleton application was built for Composer. This makes setting up a new Slim Framework application quick and easy.

## Install the Application

Run this command from the directory in which you want to install your new Slim Framework application. You will require PHP 7.4 or newer.

```bash
git clone https://github.com/bbxmbb/my-slim.git .
```
Then

```bash
composer install
composer dump-autoload
```
After that 
1. create a .env file from .env.example
2. change the set base Path to the current 

## Clone old version

You can clone different release by using this command

```bash
git clone --branch v1.0 --single-branch https://github.com/bbxmbb/my-slim.git .
``````
## For shared host(like mine)
You might need to download customs autoload

```bash
php -r "copy('https://getcomposer.org/download/latest-stable/composer.phar', 'composer');"
chmod +x composer
```

then follow the same step
```bash
./composer install
./composer dump-autoload
```

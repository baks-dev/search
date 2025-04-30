# BaksDev Search

[![Version](https://img.shields.io/badge/version-7.2.5-blue)](https://github.com/baks-dev/search/releases)
![php 8.4+](https://img.shields.io/badge/php-min%208.4-red.svg)
[![packagist](https://img.shields.io/badge/packagist-green)](https://packagist.org/packages/baks-dev/search)

Модуль поиска

## Установка модуля

Предварительно: необходима установка [redis-stack-server](REDIS.md)

``` bash
$ composer require baks-dev/search
```

## Настройки

Задаем настройки Redis Stack

``` bash
sudo nano /opt/redis-stack/etc/redis-stack.conf
```

Пример настройки redis-stack.conf:

``` redis
port 6579
daemonize no
requirepass <YOU_PASSWORD>
```

В .env необходимо указать параметры

``` dotenv
REDIS_SEARCH_HOST=localhost
REDIS_SEARCH_PORT=6579
REDIS_SEARCH_TABLE=0
REDIS_SEARCH_PASSWORD=<YOU_PASSWORD>
```

Перезапускаем Redis Stack

``` bash
sudo systemctl restart redis-stack-server
```

Проверка работы Redis

```bash
redis-cli -p 6579
127.0.0.1:6579> AUTH <YOU_PASSWORD>
OK
127.0.0.1:6579> PING
PONG
```

Ctrl+D чтобы выйти

##### Команда для индексации

``` bash
php bin/console baks:redis:search:index
```

## Тесты

``` bash
$ php bin/phpunit --group=search
```

## Лицензия ![License](https://img.shields.io/badge/MIT-green)

The MIT License (MIT). Обратитесь к [Файлу лицензии](LICENSE.md) за дополнительной информацией.

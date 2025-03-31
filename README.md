# BaksDev Search

[![Version](https://img.shields.io/badge/version-7.2.0-blue)](https://github.com/baks-dev/search/releases)
![php 8.4+](https://img.shields.io/badge/php-min%208.4-red.svg)

Модуль поиска

## Предварительно: необходима установка redis-stack-server
https://redis.io/docs/latest/operate/oss_and_stack/install/install-stack/linux/


## Установка модуля

``` bash
$ composer require baks-dev/search
```

## Настройки

Задаем настройки Redis Stack
``` bash

nano /opt/redis-stack/etc/redis-stack.conf
ПРИМЕР:
 port 6579
 daemonize no
 requirepass Po4ySG7W2EaOl4c
```

Перезапуск Redis Stack

``` bash
sudo systemctl restart redis-stack-server
```

В .env необходимо указать параметры
REDIS_SEARCH_PORT=6579
REDIS_SEARCH_TABLE=0

## Команда для индексации
``` bash
php bin/console baks:redis:search:index
```

## Тесты

``` bash
$ php bin/phpunit --group=search
```

## Лицензия ![License](https://img.shields.io/badge/MIT-green)

The MIT License (MIT). Обратитесь к [Файлу лицензии](LICENSE.md) за дополнительной информацией.

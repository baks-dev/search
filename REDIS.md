## Установка

#### Импортируйте GPG-ключ и добавьте репозиторий:

``` bash
curl -fsSL https://packages.redis.io/gpg | sudo gpg --dearmor -o /usr/share/keyrings/redis-archive-keyring.gpg
echo "deb [signed-by=/usr/share/keyrings/redis-archive-keyring.gpg] https://packages.redis.io/deb $(lsb_release -cs) main" | sudo tee /etc/apt/sources.list.d/redis.list
```

#### Обновление списка пакетов

```bash
sudo apt update
```

#### Установка redis-stack-server

```bash
sudo apt install redis-stack-server -y
```

## Запуск и проверка сервера

После установки Redis Stack Server должен автоматически запуститься. Проверьте статус:

```bash 
sudo systemctl status redis-stack-server
```

Если сервер не запущен, выполните:

```bash
sudo systemctl start redis-stack-server
```

для автозапуска при загрузке

```bash
sudo systemctl enable redis-stack-server  
```

#### Конфигурация:

Основной конфигурационный файл находится в /opt/redis-stack/etc/redis-stack.conf.

##### Порты:

По умолчанию Redis слушает 6379, а Redis Insight (веб-интерфейс) — 8001.

## Redis Insight (GUI)

Redis Stack включает веб-интерфейс Redis Insight. После установки он доступен по адресу:

```
 http://ваш_сервер:8001 
 ```
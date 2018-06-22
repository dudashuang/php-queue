# php-queue

![](https://img.shields.io/badge/build-passing-brightgreen.svg)
![](https://img.shields.io/badge/php->=7.0.0-bule.svg)
![](https://img.shields.io/badge/license-MIT-yellow.svg)

A php client for message queue which is one of RabbitMQ, Kafka and Redis.

### Requirement

- redis

  ```shell
    sudo apt-get install redis-server
  ```

- RabbitMQ

    [http://dudashuang.com/rabbitmq/](http://dudashuang.com/rabbitmq/)

- Kafka

    [http://dudashuang.com/kafka/](http://dudashuang.com/kafka/)

### Install

- composer

  ```shell
    composer require dudashuang/php-queue
  ```

### Base Usage

- examples

  - [job](examples/Jobs/TestJob.php)
  - [event](examples/Events/PaySuccessEvent.php)
  - [listener](examples/Listeners/SendListener.php)
  - [ListenerServiceProvider](examples/ListenerServiceProvider.php)

- create a application 

  - if your driver is redis:

    ```php
    <?php
    require __DIR__ . '/vendor/autoload.php';
    
    $application = new \Lily\Application([
        'driver' => 'redis',
        'scheme' => 'tcp',
        'host' => 'localhost',
        'port' => 6379,
        'default_queue' => 'queue_name',
    ]);
    ```

  - kafka:

    ```php
    <?php
    require __DIR__ . '/vendor/autoload.php';
    
    $application = new \Lily\Application([
        'driver' => 'kafka',
        'brokers' => [
            ['host' => 'localhost', 'port' => 9092],
            ...
        ],
    ]);
    ```

  - rebbitmq:

    ```php
    <?php
    require __DIR__ . '/vendor/autoload.php';
    
    $application = new \Lily\Application([
        'driver'   => 'rabbitmq',
        'host'     => 'localhost',
        'port'     => 5672,
        'username' => 'guest',
        'password' => 'guest',
    ]);
    ```

- dispatch a job

  - default queue

    ```php
    for ($i=0; $i<10; $i++) {
        $application->dispatch(new TestJob(['a' => $i]));
    }
    ```

  - other queue

    ```php
    $application->dispatch((new TestJob(['a' => 'haha']))->set_queue($queue_name));
    ```

- dispatch a event

  ```php
  $application->dispatch(new TestEvent(['a' => 1]));
  ```

- create a consumer

  ```php
  $application−>consume($queue_name);
  ```

- create a listener

  ```php
  $application->listen(new TestListener(), ['TestEvent', 'TestEvent1']);
  ```

### TODO

- add RocketMQ driver

- add delay queue

### Additional

you can use supervisor to manage consumer

- install 

  ```shell
    sudo apt-get install supervisor
  ```

- supervisor config

  ```shell
    sudo vim /etc/supervisor/conf.d/guopika.config
  ```

  ```
    [program:guopika-worker]
    process_name=%(program_name)s_%(process_num)02d
    command=php /path_to_listen_queue/cli_func
    autostart=true
    autorestart=true
    user=www-data
    numprocs=4
    redirect_stderr=true
    stdout_logfile=/path/worker.log
  ```

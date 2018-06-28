# php-queue

![](https://img.shields.io/badge/build-passing-brightgreen.svg)
![](https://img.shields.io/badge/php->=7.0.0-red.svg)
![](https://img.shields.io/badge/license-MIT-yellow.svg)
![](https://img.shields.io/badge/version-1.1.0-green.svg)

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
  
- create a driver

  - redis:
  
    ```
    <?php
    require __DIR__ . '/vendor/autoload.php';
    
    $connector  = new Lily\Connectors\RedisConnector([
        'scheme'             => 'tcp',
        'host'               => '127.0.0.1',
        'port'               => 6379,
        'read_write_timeout' => 0,
    ]);
    $driver = new Lily\Drivers\Redis($connector);
    ```
    
  - kafka:
  
    ```
    <?php
    require __DIR__ . '/vendor/autoload.php';
    
    $connector  = new Lily\Connectors\KafkaConnector([
        'brokers' => [
            ['host' => 'localhost', 'port' => 9092],
        ],
    ]);
    $driver = new Lily\Drivers\Kafka($connector);
    ```
    
  - rabbitmq: 
  
    ```
    <?php
    require __DIR__ . '/vendor/autoload.php';
    
    $connector  = new Lily\Connectors\RabbitMQConnector([
        'host'     => 'localhost',
        'port'     => 5672,
        'username' => 'guest',
        'password' => 'guest',
    ]);
    $driver = new Lily\Drivers\RabbitMQ($connector);
    ```

- create a application 

    ```
    <?php
    require __DIR__ . '/vendor/autoload.php';
        
    $app = new Lily\Application($driver, [
        'deafult_queue' => 'default-queue',
        'failed_queue'  => 'failed-queue',
    ]);
    ```

- dispatch a job

  - default queue

    ```
    for ($i=0; $i<10; $i++) {
        $application->dispatch(new TestJob('hello', new LilyTest\TestModel(1, 2)));
    }
    ```

  - other queue

    ```
    $application->dispatch((new TestJob(...))->set_queue($queue_name));
    ```

- dispatch a event

  ```
  $application->dispatch(new TestEvent(...));
  ```

- create a consumer

  ```
  $application−>consume($queue_name);
  ```

- create a listener

  ```
  $application->listen('LilyTest\Listeners\SendListener', ['TestEvent', 'TestEvent1']);
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

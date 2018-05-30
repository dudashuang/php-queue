# php-queue 
![](https://img.shields.io/badge/build-passing-brightgreen.svg)
![](https://img.shields.io/badge/php->=5.6.0-bule.svg)
![](https://img.shields.io/badge/license-MIT-yellow.svg)


a simple libraries for working with event and task queues.

### Requirement

* redis

    ```bash
    sudo apt-get install redis-server
    ```

### Base Usage

* create new job

    ```
    <?php
    namespace GPK\Jobs;
    
    use Lily\Jobs\BaseJob;
    
    class TestJob extends BaseJob {
        public $a;
        public $b;
    
        public function handle() {
            echo "hello world \n";
            echo "queue: " . $this->get_queue() . "\n";
            echo "a:" . $this->a . "\n";
            echo "b:" . $this->b . "\n";
            echo "filed_times: " . $this->get_failed_times() . "\n";
          //  $this->mark_as_failed();
        }
    }
    
    ```

* dispatch the created job 

    ```
    <?php
    require __DIR__ . '/vendor/autoload.php';
    
    class Test {
        use \Lily\DispatchAble;
    }
    
    $test = new Test();
    
    for ($i = 0; $i < 10; $i++) {
        // default 
        $test->dispatch(new GPK\Jobs\TestJob(['a' => $i, 'b' => $i + 1]));
        
        // chose queue
        $test->dispatch(new GPK\Jobs\TestJob(['a' => $i, 'b' => $i + 1]), 'queue_name');
        
        // chose redis connection
        $test->dispatch(new GPK\Jobs\TestJob(['a' => $i, 'b' => $i + 1]), 'queue_name', [
            'scheme' => 'tcp',
            'host'   => '127.0.0.1',
            'port'   => 6379,
        ]);
        
        // set execute time
        $test->dispatch((new GPK\Jobs\TestJob(['a' => $i, 'b' => $i + 1]))->execute_at('2018-05-11 12:58:00'));
    }
    ```

* create listener 

    ```
    <?php
    require __DIR__ . '/vendor/autoload.php';
    
    class TestConsume {
        use \Lily\ConsumeAble;
    }
    
    $test = new TestConsume();
    
    $test->consume();
    
    ```


### Additional

you can use supervisor to manage consumer

* install 

    ```bash
    sudo apt-get install supervisor
    ```

* supervisor config

    ```bash
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

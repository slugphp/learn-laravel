<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Request extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request {action}';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     *
     * @return mixed
     */
    public function handle()
    {
        $action = $this->argument('action');
        if (!method_exists($this, $action)) {
            return $this->error('Wrong action!');
        }
        return  $this->$action();
    }

    public function sendAsync()
    {
        $client = new \GuzzleHttp\Client();
        $request = new \GuzzleHttp\Psr7\Request('GET', 'http://123.103.58.92/api/');
        echo date('Y-m-d H:i:s') . substr((string) microtime(), 1, 6), br();
        $promise = $client->sendAsync($request);
        $promise = $client->sendAsync($request);
        $promise = $client->sendAsync($request);
        $request2 = new \GuzzleHttp\Psr7\Request('GET', 'https://www.google.com/');
        $promise2 = $client->sendAsync($request2);
        // 异步且等待返回，相当于chrome同时打开多个标签
        $promise->wait();
        echo "done...", br();
        echo date('Y-m-d H:i:s') . substr((string) microtime(), 1, 6), br();

    }

    public function ping()
    {
        $url = 'http://www.google.com/';
        (new \GuzzleHttp\Client)->get($url);
        echo 2333;
    }


    public function pool()
    {
        $client = new \GuzzleHttp\Client();

        $requests = function ($total) {
            $uri = 'http://127.0.0.1:8126/guzzle-server/perf';
            for ($i = 0; $i < $total; $i++) {
                yield new \GuzzleHttp\Psr7\Request('GET', $uri);
            }
        };

        $pool = new \GuzzleHttp\Pool($client, $requests(100), [
            'concurrency' => 5,
            'fulfilled' => function ($response, $index) {
                // this is delivered each successful response
            },
            'rejected' => function ($reason, $index) {
                // this is delivered each failed request
            },
        ]);

        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();
    }


}


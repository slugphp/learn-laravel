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

    public function postAsync()
    {
        $client = new \GuzzleHttp\Client();
        $url = 'http://127.0.0.1/';
        for ($i=0; $i < 1000; $i++) {
            $data = [];
            $data[] = $i;
            $data[] = time();
            $promise = $client->postAsync($url, ['json' => $data]);
            echo $i, ' ';
        }
        try {
            $promise->wait();
        } catch (\Exception $e) {
            $promise->wait();
            echo 'error1 ';
        } catch (\GuzzleHttp\Exception $e) {
            echo 'error2 ';
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            echo 'ServerException ';
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            echo 'RequestException ';
        } finally {
            echo "Finally.\n";
        }
    }

    public function ping()
    {
        $url = 'http://www.google.com/';
        (new \GuzzleHttp\Client)->get($url);
        echo 2333;
    }


    public function pool()
    {
        $start = microtime(true);

        $client = new \GuzzleHttp\Client();

        $requests = function ($total) {
            $uri = 'http://127.0.0.1/';
            for ($i = 0; $i < $total; $i++) {
                $data = [];
                $data['i'] = $i;
                $data['t'] = time();
                yield new \GuzzleHttp\Psr7\Request(
                    'POST',
                    $uri,
                    ['Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8'],
                    http_build_query($data)
                );
            }
        };

        $pool = new \GuzzleHttp\Pool($client, $requests(5), [
            'concurrency' => 200,
            'fulfilled' => function ($response, $index) {
                var_dump('success ' . $response->getBody());
            },
            'rejected' => function ($reason, $index) {
                var_dump('error ' . $index);
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();
        echo microtime(true) - $start;
    }


}


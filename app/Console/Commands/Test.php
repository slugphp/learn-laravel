<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

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
     * @return mixed
     */
    public function handle()
    {
        // test
        $res = simpleCurl('http://bang.qq.com/actcenter/queryFilterActList', [
            'method' => 'post',
            'data' => [
                'game' => 'dnf',
                'index' => 8,
                'count' => 8,
                'type' => 2,
                'subset' => 1,
                'subtype' => '',
                'feature' => '',
                'feature_free_flag' => '',
            ],
            'header' => 'Host: bang.qq.com
User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:62.0) Gecko/20100101 Firefox/62.0
Accept: application/json, text/javascript, */*; q=0.01
Accept-Language: zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2
Accept-Encoding: gzip, deflate
Referer: http://bang.qq.com/actcenter/index/dnf/1
Content-Type: application/x-www-form-urlencoded
X-Requested-With: XMLHttpRequest
Content-Length: 77
Cookie:
Connection: keep-alive
Pragma: no-cache
Cache-Control: no-cache
',
        ]);
        die(var_dump($res));
        return;

        // (new \App\Models\Lantern)->checkUpdate();
        (new \App\Models\Lantern)->checkNewIssues();
        die;
        $client = new \GuzzleHttp\Client();
        $request = new \GuzzleHttp\Psr7\Request('GET', 'https://www.github.com');
        echo date('Y-m-d H:i:s') . substr((string) microtime(), 1, 6), br();
        $promise = $client->sendAsync($request)->then(function ($response) {
            echo 'I completed! 233 ' . $response->getStatusCode(), br();
            echo date('Y-m-d H:i:s') . substr((string) microtime(), 1, 6), br();
        });
        $promise = $client->sendAsync($request)->then(function ($response) {
            echo 'I completed! 234 ' . $response->getStatusCode(), br();
            echo date('Y-m-d H:i:s') . substr((string) microtime(), 1, 6), br();
        });
        $promise = $client->sendAsync($request)->then(function ($response) {
            echo 'I completed! 235 ' . $response->getStatusCode(), br();
            echo date('Y-m-d H:i:s') . substr((string) microtime(), 1, 6), br();
        });
        $promise = $client->sendAsync($request)->then(function ($response) {
            echo 'I completed! 236 ' . $response->getStatusCode(), br();
            echo date('Y-m-d H:i:s') . substr((string) microtime(), 1, 6), br();
        });
        // $promise233->wait();
        // $promise234->wait();
        // $promise235->wait();
        $promise->wait();
        echo "Waiting...", br();
        echo date('Y-m-d H:i:s') . substr((string) microtime(), 1, 6), br();
    }
}

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

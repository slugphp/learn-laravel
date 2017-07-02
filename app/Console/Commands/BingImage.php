<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class BingImage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'BingImage';

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
        // get bing html
        $url = 'http://cn.bing.com';
        $bing = file_get_contents($url);
        // get image
        $isMatched = preg_match('/g_img\=\{url\:\s+\"(.*?)\",/i', $bing, $matches);
        $imageUrl = $url . $matches[1];
        $imageContent = file_get_contents($imageUrl);
        // save
        $imageName = md5($imageUrl) . '.' . pathinfo($imageUrl)['extension'];
        \Storage::disk('public')->put("BingImage/$imageName", $imageContent);
        simple_dump( $imageName, $imageUrl);
    }
}

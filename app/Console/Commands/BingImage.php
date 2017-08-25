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
        \Log::getMonolog()->popHandler();
        \Log::useFiles('storage/logs/BingImage.log');
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
        if (!$isMatched) return \Log::info('BingImage: Get bing.com false.');

        $imageUrl = $url . $matches[1];
        $imageContent = file_get_contents($imageUrl);
        if (!$imageContent) return \Log::info('BingImage: Get bing image false.');

        // save
        $imageName = md5($imageUrl) . '.' . pathinfo($imageUrl)['extension'];
        $publicStorage = \Storage::disk('public');
        $publicStorage->put("BingImage/$imageName", $imageContent);
        \Log::info("BingImage: Save $imageName.");

        // delete
        foreach ($publicStorage->files('BingImage') as $file) {
            $time = $publicStorage->lastModified($file);
            if ($time < (time() - 86400 * 20)) {
                $publicStorage->delete($file);
                \Log::info("BingImage: Delete $file.");
            }
        }
    }
}

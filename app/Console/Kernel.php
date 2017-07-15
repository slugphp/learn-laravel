<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\Inspire::class,
        \App\Console\Commands\RegisterUser::class,
        \App\Console\Commands\Stock::class,
        \App\Console\Commands\PingGoogleIp::class,
        \App\Console\Commands\BingImage::class,
        \App\Console\Commands\Request::class,
        \App\Console\Commands\Test::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // echo "Start at ", date('Y-m-d H:i:s'), '  ';
        // 检测lantern更新
        $schedule->call(function () {
            $lantern = new \App\Models\sendLanternEmail;
            $lantern->check();
        })->cron('* */1 * * * *');
        // 更新壁纸
        $schedule->command('BingImage')->cron('* */5 * * * *');
    }
}

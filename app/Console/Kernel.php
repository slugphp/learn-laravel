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
        // 检测lantern更新
        $schedule->call(function () {
            (new \App\Models\SendLanternEmail)->check();
        })->cron('0 */1 * * * *');
        // 更新壁纸
        $schedule->command('BingImage')->cron('0 */5 * * * *');
    }
}

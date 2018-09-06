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
        Commands\RegisterUser::class,
        Commands\Run::class,
        Commands\Stock::class,
        Commands\Lantern::class,
        Commands\PingGoogleIp::class,
        Commands\BingImage::class,
        Commands\Request::class,
        Commands\Test::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

        echo date('Y-m-d H:i:s'), PHP_EOL;

        // 检测lantern更新
        $schedule->call(function () {
            (new \App\Models\Lantern)->checkUpdate();
        })->cron('*/31 * * * *');

        // 检测 getlantern/forum 精华 issue
        // $schedule->call(function () {
        //     (new \App\Models\Lantern)->checkNewIssues();
        // })->cron('*/13 * * * *');

        // 在 getlantern/lantern/issues 里发自己的邀请码
        // $schedule->call(function () {
        //     (new \App\Models\Lantern)->checkNewAd();
        // })->cron('*/19 * * * *');

        // 新的DNF公告、活动邮件通知给我
        $schedule->call(function () {
            (new \App\Models\Dnf)->checkNews();
        })->cron('*/23 * * * *');

        // 新的DNF公告、活动邮件通知给我
        $schedule->call(function () {
            (new \App\Models\Damai)->checkLijian();
        })->cron('59 * * * *');

        // 更新壁纸
        $schedule->command('BingImage')->cron('7 */3 * * *');
    }
}

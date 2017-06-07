<?php

namespace App\Console\Commands;

use Storage;
use Illuminate\Console\Command;

class PingGoogleIp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pingGoogleIp';

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
     * 结果保存在：storage/app/PingGoogleIp.log
     * 调试查看：tail -f storage/app/tmp
     *
     * @return mixed
     */
    public function handle()
    {

        $ipArr = [
            "216.58.200.238",
            "172.217.24.14",
            "172.217.27.142",
            "216.58.200.46",
        ];

        echo date('Y-m-d H:i:s') . substr((string) microtime(), 1, 6), "\r\n";
        $results = [];
        $failureIp = [];
        for ($j = 0; $j < 5; $j++) {
            foreach ($ipArr as $findIp) {
                $endIp = @end(@explode('.', $findIp));
                for ($i = 0; $i < 256; $i++) {
                    $ip = preg_replace("/$endIp$/iUs", $i, $findIp);
                    // 失败的不重复
                    if (in_array($ip, $failureIp)) continue;
                    // 时间判断失败
                    $pingRes = $this->curlIp($ip);
                    if ($pingRes < 0) {
                        $failureIp[] = $ip;
                    } else {
                        $results[$ip][] = $pingRes;
                    }
                }
            }
        }
        echo date('Y-m-d H:i:s') . substr((string) microtime(), 1, 6), "\r\n";
        $saveData = [];
        foreach ($results as $ip => $timeArr) {
            $avg = array_sum($timeArr) / count($timeArr);
            $saveData[$ip] = $avg;
        }
        asort($saveData);
        $resFile = "PingGoogleIp.log";
        Storage::put($resFile, json_encode($saveData));
    }

    function curlIp($ip)
    {
        $url = "http://$ip";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 222);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        $response = curl_exec($ch);
        $time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);
        // print
        $headerArr = explode("\n", $response);
        if (strpos($headerArr[0], '301') && strpos($headerArr[1], 'google.com')) {
            $res = "success";
        } else {
            $res = "failure";
        }
        Storage::put('tmp', "$ip $res $time \r\n");
        return $res == 'success' ? $time : -1;
    }

}

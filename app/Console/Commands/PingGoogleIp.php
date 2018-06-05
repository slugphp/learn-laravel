<?php

namespace App\Console\Commands;

use Storage;
use Cache;
use Illuminate\Console\Command;

class PingGoogleIp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'PingGoogleIp';

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
            "108.177.97.*",
            "172.217.20.*",
            "172.217.24.*",
            "172.217.27.*",
            "172.217.4.*",
            "172.217.5.*",
            "172.217.8.*",
            "172.217.9.*",
            "203.208.39.*",
            "216.58.192.*",
            "216.58.193.*",
            "216.58.197.*",
            "216.58.199.*",
            "216.58.200.*",
            "216.58.203.*",
            "216.58.208.*",
            "216.58.216.*",
            "216.58.217.*",
            "216.58.219.*",
            "216.58.221.*",
            "46.82.174.*",
            "59.24.3.*",
            "74.125.200.*",
            "74.125.23.*",
            "78.16.49.*",
            "8.7.198.*",
            "93.46.8.*",
        ];

        $results['time'] = ['start' => date('Y-m-d H:i:s')];
        $times = [];
        $resFile = "PingGoogleIp.log";
        foreach ($ipArr as $findIp) {
            for ($i = 0; $i < 256; $i++) {
                $ip = str_replace('*', $i, $findIp);
                $pingRes = $this->curlIp($ip);
                if ($pingRes > 0) {
                    $results['ping'][$ip]['count'] = 1;
                    $results['ping'][$ip]['avg_time'] = $times[$ip][] = $pingRes;
                }
                $results['time']['1CircleDone'] = date('Y-m-d H:i:s');
                Storage::put($resFile, indentToJson($results));
            }
        }

        Storage::put($resFile, indentToJson($results));

        for ($i = 2; $i < 12; $i++) {
            if (!array_key_exists('ping', $results['ping'])) break;
            foreach ($results['ping'] as $ip => $data) {
                $pingRes = $this->curlIp($ip);
                if ($pingRes > 0) {
                    $times[$ip][] = $pingRes;
                    $results['ping'][$ip]['count']++;
                    $results['ping'][$ip]['avg_time'] = array_sum($times[$ip]) / count($times[$ip]);
                }
            }
            uasort($results, function ($a, $b) {
                if ($a['count'] == $b['count']) return 0;
                return ($a['count'] > $b['count']) ? 1 : -1;
            });
            $results['time']["{$i}CircleDone"] = date('Y-m-d H:i:s');
            Storage::put($resFile, indentToJson($results));
        }
    }

    function curlIp($ip)
    {
        $url = "https://$ip/humans.txt";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // 设置超时
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 333);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        // 请求 www.google.com 指到 $ip
        $header = array(
                'X-Forwarded-For: 1.1.1.1',
                'Host: www.google.com'
            );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);
        // print
        $headerArr = explode("\n", $response);
        if (strpos($headerArr[0], '200')) {
            $res = "success";
        } else {
            $res = "failure";
        }
        echo $print = "$ip $res $time \r\n";
        // $res == 'success' && die();    // test
        Storage::put('tmp', $print);
        return $res == 'success' ? $time :
            ($response == '' ? -1 : -2);
    }

}

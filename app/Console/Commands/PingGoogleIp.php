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
        // $this->poolPing();die;

        $ipArr = [
            '173.194.223.*',
            '216.58.200.*',
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

    public function poolPing()
    {
        $start = date('Y-m-d H:i:s') . substr((string) microtime(), 1, 6);

        // 获取IP列表
        $findIpArr = [
            '172.217.17.*',
            '172.217.24.*',
            '172.217.25.*',
            '172.217.27.*',
            '172.217.5.*',
            '203.208.39.*',
            '216.58.192.*',
            '216.58.199.*',
            '216.58.200.*',
            '216.58.206.*',
            '216.58.217.*',
            '216.58.219.*',
        ];
        $ipArr = [];
        foreach ($findIpArr as $findIp) {
            for ($i = 0; $i < 256; $i++) {
                $ip = str_replace('*', $i, $findIp);
                $ipArr[] = $ip;
            }
        }

        Storage::append('google.log', "====== start $start ======");

        // 异步请求池
        $requests = function ($ipArr) {
            foreach ($ipArr as $ip) {
                yield new \GuzzleHttp\Psr7\Request('GET', "https://$ip/ncr");
            }
        };
        $client = new \GuzzleHttp\Client();
        $pool = new \GuzzleHttp\Pool($client, $requests($ipArr), [
            'concurrency' => 20,
            'fulfilled' => function ($response, $index) use ($ipArr) {
                if ($response->getStatusCode() == 302) {
                    Storage::append('google.log', $ipArr[$index]);
                }
            },
            'rejected' => function ($reason, $index) {
                \Log::info("rejected $index", [$reason]);
            },
            'options' => [
                'headers' => [
                    'X-Forwarded-For' => '1.1.1.1',
                    'Host' => 'www.google.com'
                ],
                'verify' => false,
                'allow_redirects' => false,
                'curl' => [
                    CURLOPT_CONNECTTIMEOUT_MS => 333
                ],
                'timeout' => 1,
            ]
        ]);
        $pool->promise()->wait();

        $end = date('Y-m-d H:i:s') . substr((string) microtime(), 1, 6);
        Storage::append('google.log', "====== end $end ======\r\n");
        echo $start, "\r\n", $end;

    }

    public function traversalAllIpAddress()
    {
        $key = 'ping-iplong-2017-06-14';
        $ipLong = Cache::get($key) ?: 0;
        while ($ipLong < 4294967295) {
            if ($skipIplong = $this->isIplongInnerUse($ipLong)) {
                $ipLong = $skipIplong;
                continue;
            }
            $ipAddress = long2ip($ipLong);
            $pingRes = $this->curlIp($ipAddress);
            if ($pingRes > 0) {    // 成功
                Storage::append('google-ip-address', "$ipAddress \r\n");
                $nextIplong = $ipLong + (255 - end(explode('.', $ipAddress))) + 1;
                $ipLong = $nextIplong;
            } else if ($pingRes == -2) {    // 有反应不是这段
                $nextIplong = $ipLong + (255 - end(explode('.', $ipAddress))) + 1;
                $ipLong = $nextIplong;
            } else {    // 没反应
                $ipLong++;
            }
            Cache::forever($key, $ipLong);
        }
    }

    function isIplongInnerUse($ipLong)
    {
        // 0.0.0.0 – 0.255.255.255  软件
        if ($ipLong >= 0 && $ipLong <= 16777215) return 16777215 + 1;
        // 10.0.0.0 – 10.255.255.255  专用网络
        if ($ipLong >= 167772160 && $ipLong <= 184549375) return 184549375 + 1;
        // 100.64.0.0 – 100.127.255.255  专用网络
        if ($ipLong >= 1681915904 && $ipLong <= 1686110207) return 1686110207 + 1;
        // 127.0.0.0 – 127.255.255.255  主机
        if ($ipLong >= 2130706432 && $ipLong <= 2147483647) return 2147483647 + 1;
        // 169.254.0.0 – 169.254.255.255  子网
        if ($ipLong >= 2851995648 && $ipLong <= 2852061183) return 2852061183 + 1;
        // 172.16.0.0 – 172.31.255.255  专用网络
        if ($ipLong >= 2886729728 && $ipLong <= 2887778303) return 2887778303 + 1;
        // 192.0.0.0 – 192.0.0.255  专用网络
        if ($ipLong >= 3221225472 && $ipLong <= 3221225727) return 3221225727 + 1;
        // 192.0.2.0 – 192.0.2.255  文档
        if ($ipLong >= 3221225984 && $ipLong <= 3221226239) return 3221226239 + 1;
        // 192.88.99.0 – 192.88.99.255  互联网
        if ($ipLong >= 3227017984 && $ipLong <= 3227018239) return 3227018239 + 1;
        // 192.168.0.0 – 192.168.255.255  专用网络
        if ($ipLong >= 3232235520 && $ipLong <= 3232301055) return 3232301055 + 1;
        // 198.18.0.0 – 198.19.255.255  专用网络
        if ($ipLong >= 3323068416 && $ipLong <= 3323199487) return 3323199487 + 1;
        // 198.51.100.0 – 198.51.100.255  文档
        if ($ipLong >= 3325256704 && $ipLong <= 3325256959) return 3325256959 + 1;
        // 203.0.113.0 – 203.0.113.255  文档
        if ($ipLong >= 3405803776 && $ipLong <= 3405804031) return 3405804031 + 1;
        // 224.0.0.0 – 239.255.255.255  互联网
        if ($ipLong >= 3758096384 && $ipLong <= 4026531839) return 4026531839 + 1;
        // 240.0.0.0 – 255.255.255.254  互联网
        if ($ipLong >= 4026531840 && $ipLong <= 4294967294) return 4294967294 + 1;
        // 255.255.255.255  子网
        if ($ipLong == 4294967295) return 4294967295 + 1;

        return false;
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
        ob_start();$s=array($headerArr, $response);foreach($s as $v){var_dump($v);}die('<pre style="white-space:pre-wrap;word-wrap:break-word;">'.preg_replace(array('/\]\=\>\n(\s+)/m','/</m','/>/m'),array('] => ','&lt;','&gt;'),ob_get_clean()).'');
        if (strpos($headerArr[0], '302') && strpos($headerArr[1], 'google.com')) {
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

    function __destruct()
    {

    }

}

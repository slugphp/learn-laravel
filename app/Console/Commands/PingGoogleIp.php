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
        // $this->pingGoogleIncIp2();
        $ipArr = [
            '172.217.24.*',
            '216.58.193.*',
            '216.58.200.*',
        ];

        echo date('Y-m-d H:i:s') . substr((string) microtime(), 1, 6), "\r\n";
        $results = [];
        foreach ($ipArr as $findIp) {
            // $endIp = @end(@explode('.', $findIp));
            for ($i = 0; $i < 256; $i++) {
                $ip = str_replace('*', $i, $findIp);
                $pingRes = $this->curlIp($ip);
                if ($pingRes > 0) {
                    $results[$ip] = 1;
                }

            }
        }
        $resFile = "PingGoogleIp.log";
        Storage::put($resFile, indentToJson($results) . "\r\n" . date('Y-m-d H:i:s'));

        while (true) {
            foreach ($results as $ip => $count) {
                $pingRes = $this->curlIp($ip);
                if ($pingRes > 0) {
                    $results[$ip]++;
                }
            }

            asort($results);
            Storage::put($resFile, indentToJson($results) . "\r\n" . date('Y-m-d H:i:s'));
        }
    }

    public function pingGoogleIncIp2()
    {
        $response = simpleCurl('http://bgp.he.net/jc', [
                'method' => 'post',
                'data' => 'p=f1758a4305e233c2c301dba2e9a0fdafd71&i=730faac111ec8cc6f52f6f0611fc27a4',
                'header' => '
Cookie:path=BAgiNC9zZWFyY2g%2Fc2VhcmNoJTVCc2VhcmNoJTVEPUdvb2dsZSZjb21taXQ9U2VhcmNo--cdc6217ffa439a08ce775442e5e72ec656a136d9
',
                'return' => 'all',
            ]);
        die(simple_dump($response));
        $url = 'http://bgp.he.net/search?search%5Bsearch%5D=Google&commit=Search';
        $content = simpleCurl($url, [
                'header22' => 'GET /search?search%5Bsearch%5D=17house&commit=Search HTTP/1.1
Host: bgp.he.net
Connection: keep-alive
Cache-Control: max-age=0
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8
Referer: http://bgp.he.net/search?search%5Bsearch%5D=Google&commit=Search
Accept-Encoding: gzip, deflate, sdch
Accept-Language: en,zh-CN;q=0.8,zh;q=0.6,zh-TW;q=0.4,mt;q=0.2,fr;q=0.2,pt;q=0.2,ja;q=0.2,da;q=0.2,pl;q=0.2
Cookie: c=BAgiEDIxMC41MS4xOS4y--a5feae0544685749177d639d35cb84cdd04dec0a; _bgp_session=BAh7BjoPc2Vzc2lvbl9pZEkiJWYyZTllYTdmMzFjYWVjNGRjNDRmYjgxNjg2ZjVlMGMyBjoGRUY%3D--eb0df37006be288bdbf6c073539a8956188c2760
If-None-Match: "6edea02fefcf650100488a706351f137"',
                'return' => 'all'
            ]);
        preg_match_all('/(\d+\.\d+\.\d+\.)(\d+)\/(\d+)/iUs', $content, $matches);
        $ipArr = array_values(array_unique($matches[1]));
        shuffle($ipArr);
        die(simple_dump($content));
        foreach ($ipArr as $ipPre) {
            for ($i = 0; $i < 256; $i++) {
                $ip = $ipPre . $i;
                // 时间判断失败
                $pingRes = $this->curlIp($ip);
                if ($pingRes == -2) continue 2;
            }
        }
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
        $url = "http://$ip";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 333);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 999);
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
        echo $print = "$ip $res $time \r\n\r\n$response";
        // $res == 'success' && die();    // test
        Storage::put('tmp', $print);
        return $res == 'success' ? 1 :
            ($response == '' ? -1 : -2);
    }

}

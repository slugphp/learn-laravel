<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Request extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request {action}';

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
     *
     * @return mixed
     */
    public function handle()
    {
        $action = $this->argument('action');
        if (!method_exists($this, $action)) {
            return $this->error('Wrong action!');
        }
        return  $this->$action();
    }

    public function sendAsync()
    {
        $client = new \GuzzleHttp\Client();
        $request = new \GuzzleHttp\Psr7\Request('GET', 'http://123.103.58.92/api/');
        echo date('Y-m-d H:i:s') . substr((string) microtime(), 1, 6), br();
        $promise = $client->sendAsync($request);
        $promise = $client->sendAsync($request);
        $promise = $client->sendAsync($request);
        $request2 = new \GuzzleHttp\Psr7\Request('GET', 'https://www.google.com/');
        $promise2 = $client->sendAsync($request2);
        // 异步且等待返回，相当于chrome同时打开多个标签
        $promise->wait();
        echo "done...", br();
        echo date('Y-m-d H:i:s') . substr((string) microtime(), 1, 6), br();

    }

    public function postAsync()
    {
        $client = new \GuzzleHttp\Client();
        $url = 'http://127.0.0.1/';
        for ($i=0; $i < 1000; $i++) {
            $data = [];
            $data[] = $i;
            $data[] = time();
            $promise = $client->postAsync($url, ['json' => $data]);
            echo $i, ' ';
        }
        try {
            $promise->wait();
        } catch (\Exception $e) {
            $promise->wait();
            echo 'error1 ';
        } catch (\GuzzleHttp\Exception $e) {
            echo 'error2 ';
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            echo 'ServerException ';
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            echo 'RequestException ';
        } finally {
            echo "Finally.\n";
        }
    }

    public function ping()
    {
        $url = 'http://www.google.com/';
        (new \GuzzleHttp\Client)->get($url);
        echo 2333;
    }


    public function pool()
    {
        $start = microtime(true);

        $client = new \GuzzleHttp\Client();

        $requests = function ($total) {
            $uri = 'http://127.0.0.1/';
            for ($i = 0; $i < $total; $i++) {
                $data = [];
                $data['i'] = $i;
                $data['t'] = time();
                yield new \GuzzleHttp\Psr7\Request(
                    'POST',
                    $uri,
                    ['Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8'],
                    http_build_query($data)
                );
            }
        };

        $pool = new \GuzzleHttp\Pool($client, $requests(5), [
            'concurrency' => 200,
            'fulfilled' => function ($response, $index) {
                var_dump('success ' . $response->getBody());
            },
            'rejected' => function ($reason, $index) {
                var_dump('error ' . $index);
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();
        echo microtime(true) - $start;
    }


    public function getGdrundeUrl()
    {
        $url = 'http://guide.gdrunde.com/runde.guide/guide/getUrl';
        $client = new \GuzzleHttp\Client();
        $cities = ["青海", "江西", "四川", "吉林", "陕西", "安徽", "福建", "江苏", "云南", "北京", "上海", "广东", "湖北", "辽宁", "广西", "山东", "新疆", "黑龙江", "内蒙古", "浙江", "河南", "山西", "湖南", "海南", "重庆", "贵州", "西藏", "天津", "甘肃", "河北", "宁夏"];
        $res = [];
        foreach ($cities as $city) {
            $res[$city] = (string) $client->post($url, [
                'form_params' => ['area' => $city, ]
                ]
            )->getBody();
        }
        die(simple_dump($res));
    }


    public function getGdrundeCheckQualification()
    {
        $areas = ["青海", "江西", "四川", "吉林", "陕西", "安徽", "福建", "江苏", "云南", "北京", "上海", "广东", "湖北", "辽宁", "广西", "山东", "新疆", "黑龙江", "内蒙古", "浙江", "河南", "山西", "湖南", "海南", "重庆", "贵州", "西藏", "天津", "甘肃", "河北", "宁夏"];
        $studys['中专'] = [
            "护理", "农村医学", "药剂", "中医护理", "中医", "藏区医疗与藏药", "维区医疗与藏药", "蒙区医疗与藏药", "中医康复保健", "中药", "中药制药", "制药技术", "生物技术制药", "药品食品检验",
        ];
        $studys['大专'] = [
            "生物实验技术", "生物技术及应用", "生物化工工艺", "微生物技术及应用", "应用化工技术", "有机化工生产技术", "精细化学品生产技术", "工业分析与检验", "生化制药技术", "生物制药技术", "化学制药技术", "中药制药技术", "药物制剂技术", "药物分析技术", "食品药品监督管理", "药物质量检测技术", "药品经营与管理", "保健品开发与管理", "临床医学", "口腔医学", "中医学", "蒙医学", "藏医学", "维医学", "中西医结合", "针灸推拿", "中医骨伤", "护理", "药学", "中药", "医学检测技术", "医学生物技术", "医学影像技术", "眼视光技术", "康复治疗技术", "口腔医学技术", "医学营养", "医疗美容技术", "呼吸治疗技术", "卫生检验与检疫技术",
        ];
        $studys['本科'] = [
            "化学", "应用化学", "化学生物学", "分子科学与工程", "生物科学", "生物技术", "生物信息学", "化学工程与工艺", "制药工程", "化学工程与工业生物工程", "生物医学工程", "生物工程", "生物制药", "基础医学", "临床医学", "麻醉学", "医学影像学", "眼视光医学", "精神医学", "放射医学", "口腔医学", "预防医学", "食品卫生与营养学", "妇幼保健学", "卫生监督", "全球健康学", "中医学", "针灸推拿学", "藏医学", "蒙医学", "维医学", "壮医学", "哈医学", "中西医临床医学", "药学", "药物制剂", "临床药学", "药事管理", "药物分析", "药物化学", "海洋药学", "中药学", "中药资源与开发", "藏药学", "蒙药学", "中药制药", "中草药栽培与鉴定", "法医学", "医学检验技术", "医学实验技术", "医学影像技术", "眼视光学", "康复治疗学", "口腔医学技术", "卫生检验与疫苗", "护理学",
        ];
        $res = [];
        $url = 'http://guide.gdrunde.com/runde.guide/guide/checkQualification';
        $client = new \GuzzleHttp\Client();
        foreach ($areas as $area) {
            foreach ($studys as $k => $study) {
                foreach ($study as $v) {
                    for ($i = 1; $i <= 8; $i++) {
                        foreach (['yes', 'no'] as $insurance) {
                            $res[$area][$k][$v][$i][$insurance] = (string) $client->post($url, [
                                'form_params' => [
                                    'timeStamp' => time(),
                                    'area' => $area,
                                    'majorType' => $k,
                                    'majorName' => $v,
                                    'year' => $i,
                                    'insurance' => $insurance
                                ]
                            ])->getBody();
                            \Storage::put('checkQualification.log', indentToJson($res));
                        }
                    }
                }
            }
        }
        die(simple_dump($res));
    }
}


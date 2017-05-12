<?php

namespace App;

use DB;

class Stock
{
    public function syncIndustry()
    {
        $client = new \GuzzleHttp\Client();
        // 获取行业信息
        $industryUrl = 'http://vip.stock.finance.sina.com.cn/q/view/newSinaHy.php';
        $res = (string) $client->get($industryUrl)->getBody();
        $json = str_replace('var S_Finance_bankuai_sinaindustry = ', '', $res);
        $json = iconv('GBK', 'UTF-8', $json);
        $data = json_decode($json, true);
        // 获取
        $stockUrl = "http://vip.stock.finance.sina.com.cn/quotes_service/api/json_v2.php/Market_Center.getHQNodeData?";
        foreach ($data as $sinaNode => $str) {
            $isMatch = preg_match("/$sinaNode,(.*?),(.*?),/i", $str, $match);
            if (!$isMatch) continue;

            $industryName = $match[1];
            $num = $match[2];
            $param = [
                'page' => 1,
                'num' => $num,
                'node' => $sinaNode,
            ];
            $url = $stockUrl . http_build_query($param);
            $json = (string) $client->get($url)->getBody();
            $json = iconv('GBK', 'UTF-8', $json);
            $json = preg_replace('/([a-z]+)\:/i', "\"\\1\":", $json);
            $stockData = json_decode($json, true);
            simple_dump($json, $stockData[0]);
            die;
        }
    }
}

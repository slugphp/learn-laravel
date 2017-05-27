<?php

namespace App;

use DB;
use GuzzleHttp\Client as Http;

class Stock
{
    public function __get($name)
    {
        switch ($name) {
            case 'allStock':
                return $this->getAllStock();
                break;
            default:
                break;
        }
        return null;
    }

    public function getAllStock()
    {
        return $allStock = DB::table('stock')
            ->pluck('stock_symbol');
    }

    public function syncIndustry()
    {
        $http = new Http();
        // 获取所有行业信息
        $industryUrl = 'http://vip.stock.finance.sina.com.cn/q/view/newSinaHy.php';
        $res = (string) $http->get($industryUrl)->getBody();
        $json = str_replace('var S_Finance_bankuai_sinaindustry = ', '', $res);
        $json = iconv('GBK', 'UTF-8', $json);
        $industryData = json_decode($json, true);
        // 获取各个行业
        $stockId = [];
        $stockUrl = 'http://vip.stock.finance.sina.com.cn/quotes_service/api/json_v2.php/Market_Center.getHQNodeData?';
        foreach ($industryData as $sinaNode => $str) {
            $isMatch = preg_match("/$sinaNode,(.*?),(.*?),/i", $str, $match);
            if (!$isMatch) continue;
            // 行业URL
            $industryName = $match[1];
            $num = $match[2];
            $param = [
                'page' => 1,
                'num' => $num,
                'node' => $sinaNode,
            ];
            $url = $stockUrl . http_build_query($param);
            // 获取stock
            $json = (string) $http->get($url)->getBody();
            $json = iconv('GBK', 'UTF-8', $json);
            $json = preg_replace('/([a-z]+)\:/i', "\"\\1\":", $json);
            $stockData = json_decode($json, true);
            // 写入数据库
            foreach ($stockData as $stock) {
                if (DB::table('stock')->where('stock_symbol', $stock['symbol'])->first()) continue;
                $stockId[] = DB::table('stock')
                    ->insertGetId([
                            'stock_symbol' => $stock['symbol'],
                            'stock_name' => $stock['name'],
                            'stock_industry' => $industryName,
                            'stock_sina_node' => $sinaNode,
                        ]);
            }
        }
        echo "Insert " . count($stockId);
        die(simple_dump($stock));
    }

    public function getMoneyFlow()
    {
        $allStock = DB::table('stock')
            ->pluck('stock_symbol');
        $url = 'http://vip.stock.finance.sina.com.cn/quotes_service/api/json_v2.php/MoneyFlow.ssi_ssfx_flzjtj?daima=';

        $http = new Http();
        foreach ($allStock as $stockSymbol) {
            $url = $url . $stockSymbol;
            $res = (string) $http->get($url)->getBody();
            $data = $this->_parseSinastockJson($res);
            die(simple_dump(count($allStock), $url, $data));
        }
    }

    protected function _parseSinastockJson($json)
    {
        $json = trim($json, '(');
        $json = trim($json, ')');
        $json = iconv('GBK', 'UTF-8', $json);
        $json = preg_replace('/([a-z0-9\_]+)\:/i', "\"\\1\":", $json);
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new Exception('wrong json string', 1);
        }
        return $data;
    }

    public function getQqComment()
    {
        $url = 'http://web.group.finance.qq.com/newstockgroup/webRssService/getWebRssList2?stock_id=';
        $http = new Http();
        $qqComment = [];
        $preg = ['/\<(.*?)\:(.*?)\>/is', '/\[[a-z]+\d+(.*?)\]/is'];
        $replace = ['//@\\2: ', ''];

        foreach ($this->allStock as $stockSymbol) {
            // $stockSymbol = 'sh000001';
            // 获取评论
            $res = (string) $http->get($url . $stockSymbol)->getBody();
            $data = json_decode($res, true);
            if (!$data) continue;
            $subjectDict = $data['data']['subject_dict'];
            // 已有评论
            $hasComment = DB::table('stock_qq_comment')
                ->where('stock_symbol', $stockSymbol)
                ->first();
            // 解析评论
            $stockComment = $hasComment ? $hasComment->stock_comment : '';
            foreach (array_reverse($data['data']['rss_list']) as $comment) {
                $content = isset($comment['content']) && $comment['content']
                        ? $comment['content']
                        : $subjectDict[$comment['subject_id']]['content'];
                $contentAt = isset($comment['root_id'])
                        ? (
                            isset($subjectDict[$comment['root_id']]['content'])
                                ? $subjectDict[$comment['root_id']]['content']
                                : '已被删除'
                            )
                        : '';

                // 格式化
                $createdAt = date("Y-m-d H:i:s", strtotime($comment['created_at']));
                $content = preg_replace($preg, $replace, $content);
                $contentAt = preg_replace($preg, $replace, $contentAt);
                $content = preg_replace_callback('/./u', function (array $match) {
                        return strlen($match[0]) >= 4 ? '' : $match[0];
                    }, $content);
                $contentAt = preg_replace_callback('/./u', function (array $match) {
                        return strlen($match[0]) >= 4 ? '' : $match[0];
                    }, $contentAt);
                $content = trim($content);
                $contentAt = trim($contentAt);
                if (!$content && !$contentAt) continue;
                // 拼接
                $commentLine = "[ {$createdAt} ]". $content
                    . ($contentAt ? " //#$contentAt" : '') . "\r\n";

                if ($content && strpos($stockComment, $content) !== false) {
                    continue;
                } else {
                    $stockComment = $commentLine . $stockComment;
                }
            }
            // 更改数据
            $commentData = [
                    'stock_symbol' => $stockSymbol,
                    'stock_comment' => trim($stockComment),
                ];
            if ($hasComment) {
                $qqComment[] = DB::table('stock_qq_comment')
                    ->where('stock_symbol', $stockSymbol)
                    ->update($commentData);
            } else {
                $qqComment[] = DB::table('stock_qq_comment')
                    ->insertGetId($commentData);
            }
        }
        echo "Total " . count($qqComment);
    }
}

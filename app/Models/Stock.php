<?php

namespace App\Models;

use DB;
use GuzzleHttp\Client as Http;
use DiDom\Document;

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
        $allStock = DB::table('stock')
            ->pluck('stock_symbol');
        // foreach ($allStock as $k => $stockSymbol) {
        //     if (strpos($stockSymbol, 'sh') === false) {
        //         unset($allStock[$k]);
        //     }
        // }
        return array_values($allStock);
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
                $upData = [
                        'stock_symbol' => $stock['symbol'],
                        'stock_name' => $stock['name'],
                        'stock_industry' => $industryName,
                        'stock_trade' => $stock['trade'],
                        'stock_volume' => $stock['volume'],
                    ];
                $hasStock = DB::table('stock')->where('stock_symbol', $stock['symbol'])->first();
                if ($hasStock) {
                    $stockId[] = DB::table('stock')
                        ->where('stock_id', $hasStock->stock_id)
                        ->update($upData);
                } else {
                    $stockId[] = DB::table('stock')
                        ->insertGetId($upData);
                }
            }
        }
        echo "Insert " . count($stockId);
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
        $time = time();
        $timeOutDay = 14 * 86400;

        foreach ($this->allStock as $stockSymbol) {
            // $stockSymbol = 'sh000001';
            // 获取评论
            $res = (string) $http->get($url . $stockSymbol)->getBody();
            $data = json_decode($res, true);
            if (!$data) continue;
            $subjectDict = $data['data']['subject_dict'];
            // 已有评论，去掉旧的
            $hasComment = DB::table('stock_qq_comment')
                ->where('stock_symbol', $stockSymbol)
                ->first();
            $stockComment = $hasComment ? $hasComment->stock_comment : '';
            $stockCommentArr = [];
            foreach (explode("\r\n", $stockComment) as $k => $v) {
                $isMatch = preg_match_all('/\[([0-9-:\s]*)\]/', $v, $matches);
                if (!$isMatch) continue;
                $commentTime = strtotime(trim($matches[1][0]));
                if ($commentTime < 1) continue;
                if ($time - $commentTime > $timeOutDay) continue;
                $stockCommentArr[] = $v;
            }
            $stockComment = implode("\r\n", $stockCommentArr);
            // 解析已有评论
            foreach (array_reverse($data['data']['rss_list']) as $comment) {
                // 筛选时间
                $createdTime = strtotime($comment['created_at']);
                if ($time - $createdTime > $timeOutDay) continue;
                // 获取内容
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
                $createdAt = date("Y-m-d H:i:s", $createdTime);
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
            echo "Success $stockSymbol\r\n";
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

    public function getSinaGuba()
    {
        $url = 'http://guba.sina.com.cn/?s=bar&name=';
        $http = new Http();
        $search = ['分钟前', '今天'];
        $replace = [' min ago', ''];
        $now = time();
        $wee = strtotime(date('Y-m-d'));
        echo date('Y-m-d H:i:s') . substr((string) microtime(), 1, 6), "\r\n";
        foreach ($this->allStock as $stockSymbol) {
            $html = (string) $http->get($url . $stockSymbol)->getBody();
            $html = iconv('GBK', 'UTF-8//ignore', $html);
            $document = new Document();
            $document->loadHtml($html);
            $posts = $document->find('.table_content tr');
            $stockTitleArr = [];
            foreach ($posts as $post) {
                $a = $post->find('.linkblack');
                if (!$a) continue;
                $td = $post->find('td');
                $timeDesc = str_replace($search, $replace, trim(end($td)->text()));
                $time = strtotime($timeDesc);
                if ($time < $wee) break;
                $timeRes = date('Y-m-d H:i:s', $time);
                $title = trim($post->find('.linkblack')[0]->text());
                $stockTitleArr[] = "[ {$timeRes} ] $title";
            }
            if (empty($stockTitleArr)) continue;
            $stockComment = implode("\r\n", $stockTitleArr);
            // 更改数据
            $commentData = [
                    'stock_symbol' => $stockSymbol,
                    'stock_comment' => trim($stockComment),
                ];

            $hasComment = DB::table('stock_qq_comment')
                ->where('stock_symbol', $stockSymbol)
                ->first();
            if ($hasComment) {
                $qqComment[] = DB::table('stock_qq_comment')
                    ->where('stock_symbol', $stockSymbol)
                    ->update($commentData);
            } else {
                $qqComment[] = DB::table('stock_qq_comment')
                    ->insertGetId($commentData);
            }
        }
        echo date('Y-m-d H:i:s') . substr((string) microtime(), 1, 6), "\r\n";
        echo "Total ", count($qqComment), "\r\n";
    }
}

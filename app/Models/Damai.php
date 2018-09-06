<?php

namespace App\Models;

use Cache;
use Log;
use Storage;
use Mail;
use DiDom\Document;

class Damai
{
    protected $baseUrl = 'https://search.damai.cn/';

    function __construct()
    {
        Log::getMonolog()->popHandler();
        Log::useFiles(base_path('storage/logs/damai.log'));
    }

    /**
     * 新的
     */
    public function checkLijian()
    {
        $url = path($this->baseUrl, 'searchajax.html?keyword=%E6%9D%8E%E5%81%A5');
        $searchJson = simpleCurl($url);
        $data = json_decode($searchJson, true);
        $list = $data['pageData']['resultData'];

        if (!is_array($list)) {
            Log::info('Damai search error.');
            return;
        }

        // 公告
        $sendNews = false;
        foreach ($list as $info) {
            $news = json_encode($info, JSON_UNESCAPED_UNICODE);
            if (strpos($news, '北京') !== false) {
                $sendNews = true;
                break;
            }
        }
        if ($sendNews == false) {
            Log::info("$url Damai no new Lijian.");
            return;
        }

        // 发送邮件
        $log = [];
        $subject = 'Lijian Beijing 活动啦~~~';
        $content = "<br><br> <a href='https://search.damai.cn/search.html?keyword=%E6%9D%8E%E5%81%A5'>https://search.damai.cn/search.html?keyword=%E6%9D%8E%E5%81%A5</a>";
        foreach ($info as $k => $v) {
            $content .= "<br><br> $k : $v";
        }

        $log['send_mail_res'] = Mail::send(
            'mailbody',                 // resources/views/mailbody.blade.php
            ['body' => $content],       // views's var
            function ($message) use ($subject) {
                $message->from('wangweilong2020@163.com', 'Weilong的自动提醒');
                $message->to('wilonx@163.com', '王伟龙');
                // $message->to('wilonx@163.com', '王伟龙');
                $message->subject($subject);
        }) == 1;
        $log['subject'] = $subject;
        Log::info('Send Damai News', $log);
    }

}

<?php

namespace App\Models;

use Cache;
use Log;
use Storage;
use DiDom\Document;

class Game5173
{
    protected $baseUrl = 'http://s.5173.com';

    function __construct()
    {
        Log::getMonolog()->popHandler();
        Log::useFiles(base_path('storage/logs/game5173.log'));
    }

    /**
     * 新的
     */
    public function checkCheapDNFRole()
    {
        // DNF；价12~88；关键字：时装；不含5天；价格由低到高
        $uri = '/dnf-xptjnl-0-0-0-ybxrdb-1bsjmt%7Cum4g3u-0-0-a-a-a-12-88-0-unitprice_asc-1-0.shtml?keyword=%u65F6%u88C5';
        $url = path($this->baseUrl, $uri);

        // 获取最新
        try {
            $indexDoc = new Document($url, true, 'gb2312');
        } catch(Exception $e) {
            Log::error('Get 5173 search error.', $e->getMessage());
            return;
        }
        $roleList = $indexDoc->find('.sin_pdlbox');

        // 公告
        $sendNews = false;
        foreach ($roleList as $role) {
            // 一条信息
            $roleText = trim($role->text());
            $info = $role->first('.pdlist_info .tt a');
            $new = [];
            $new['href'] = $info->attr('href');
            $new['desc'] = trim($info->text());
            $new['price'] = trim($role->first('.pdlist_price')->text());
            // 没发过的去发送
            $key = substr(md5(json_encode($new)), 3, 9);
            if (!Cache::get($key)) {
                $sendNews[] = $new;
                Cache::forever($key, $new);
            }
        }
        if ($sendNews == false) {
            Log::info("$url 5173 no new role.");
            return;
        }

        // 发送邮件
        $log = [];
        $subject = '5173 New Role role~~~';
        $content = "<br><br> <a href='$url'>$url</a>";
        foreach ($sendNews as $new) {
            $content .= "<br><br> <a href='{$new['href']}'>{$new['price']} {$new['desc']}</a>";
        }
        $log['send_mail_res'] = send_mail($subject, $content);
        $log['subject'] = $subject;
        Log::info('Send 5173 New Role', $log);
    }

}
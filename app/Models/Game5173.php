<?php

/**
 * 5173web
 */

namespace App\Models;

use Cache;
use Log;
use Storage;
use DiDom\Document;

class Game5173
{
    protected $baseUrl = 'http://s.5173.com';

    /**
     * _con
     */
    function __construct()
    {
        Log::getMonolog()->popHandler();
        Log::useFiles(base_path('storage/logs/game5173.log'));
    }

    /**
     * 新的
     *
     * @return boolean
     */
    public function checkCheapDNFRole()
    {
        // 关键字：
        $words = [
            '不玩了', '时装',
        ];
        $sendNews = false;
        foreach ($words as $word) {
            $word = str_replace(['"', '\u'], ['', '%u'], json_encode($word));
            $uri = '/dnf-xptjnl-0-0-0-ybxrdb-1bsjmt%7Cum4g3u-0-0-a-a-a-12-88-0-unitprice_asc-1-0.shtml?keyword=';
            $url = path($this->baseUrl, $uri . $word);
            // 获取最新
            try {
                $indexDoc = new Document($url, true, 'gb2312');
            } catch(Exception $e) {
                Log::error('Get 5173 search error.', $e->getMessage());
                return;
            }
            $roleList = $indexDoc->find('.sin_pdlbox');

            // 公告
            foreach ($roleList as $role) {
                // 一条信息
                $roleText = trim($role->text());
                $info = $role->first('.pdlist_info .tt a');
                $new = [];
                $new['href'] = $info->attr('href');
                $new['desc'] = trim($info->text());
                $new['price'] = (int)trim($role->first('.pdlist_price')->text());
                // 没发过的去发送
                $key = substr(md5(json_encode($new)), 2, 9);
                if (!Cache::get($key)) {
                    $sendNews[] = $new;
                    Cache::forever($key, $new);
                }
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
            $content .= "<br><br> <a href='{$new['href']}'>New: {$new['price']} {$new['desc']}</a>";
        }
        $log['send_mail_res'] = sendMail($subject, $content);
        $log['subject'] = $subject;
        Log::info('Send 5173 New Role', $log);
        return;
    }
}

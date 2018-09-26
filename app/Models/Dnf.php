<?php

namespace App\Models;

use Cache;
use Log;
use Storage;
use Mail;
use DiDom\Document;

class Dnf
{
    protected $baseUrl = 'http://dnf.qq.com/';

    // DNF 官网地址
    protected $officialUrl = 'http://dnf.qq.com/main.shtml';

    function __construct()
    {
        Log::getMonolog()->popHandler();
        Log::useFiles(base_path('storage/logs/dnf.log'));
    }

    /**
     * 新的公告、活动邮件通知给我
     */
    public function checkNews()
    {
        // 获取最新官网信息
        try {
            $indexDoc = new Document($this->officialUrl, true, 'GB2312');
        } catch(Exception $e) {
            Log::error('Get DNF official web error.', $e->getMessage());
            return;
        }
        $newsPanel = $indexDoc->find('#news-tab .news-bd .tab-panel');

        // 公告、活动
        $sendNews = [];
        foreach ([$newsPanel[1], $newsPanel[2]] as $dom) {
            foreach ($dom->find('li') as $li) {
                $span = $li->find('span')[0];
                $a = $li->find('a')[0];
                $new['url'] = path($this->baseUrl, $a->attr('href'));
                $new['subject'] = $a->text();
                $new['date'] = $span->text();
                $new['key'] = substr(md5($new['url'].$new['subject']), 3, 7);
                if (!Cache::get($new['key'])) {
                    $sendNews[] = $new;
                    Cache::forever($new['key'], $new);
                }
            }
        }

        if ($sendNews == []) {
            Log::info('Dnf no news to send.');
            return;
        }

        // 发送邮件
        $log = [];
        $subject = $content = '有新的 DNF 活动和公告';
        foreach ($sendNews as $new) {
            $content .= "<br><br> <a href='{$new['url']}'>{$new['date']} {$new['subject']}</a>";
        }

        $log['send_mail_res'] = sendMail($subject, $content);
        $log['subject'] = $subject;
        Log::info('Send Dnf News', $log);
    }

}

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
    protected $activityUrl = 'http://bang.qq.com/actcenter/index/dnf';
    protected $tiebaUrl = 'https://tieba.baidu.com/f?kw=dnf%E6%90%AC%E7%A0%96&ie=utf-8';

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


    /**
     * 新的福利邮件通知给我
     *
     * http://bang.qq.com/actcenter/index/dnf
     */
    public function checkActivity()
    {
        // 获取最新官网信息
        try {
            $indexDoc = new Document($this->activityUrl, true);
        } catch(Exception $e) {
            Log::error('Get DNF ctivity web error.', $e->getMessage());
            return;
        }
        $activityPanel = $indexDoc->find('#JS-ActSetBox-div .s-item');

        // 公告、活动
        $sendActivity = [];
        foreach ($activityPanel as $dom) {
            $a = $dom->find('.s-item-tit')[0]->find('a')[0];
            $new = [];
            $new['url'] = $a->attr('href');
            $new['subject'] = $a->text();
            $new['time'] = trim($dom->find('.s-item-tags')[0]->text());
            $reward = trim($dom->find('.s-item-fig')[0]->text());
            $new['reward'] = preg_replace('/\s+/s', '、', $reward);
            $new['key'] = substr(md5($new['url'].$new['subject']), 4, 8);
            if (!Cache::get($new['key'])) {
                $sendActivity[] = $new;
                Cache::forever($new['key'], $new);
            }
        }

        if ($sendActivity == []) {
            Log::info('Dnf no activity to send.');
            return;
        }

        // 发送邮件
        $log = [];
        $subject = $content = '有新的 DNF 活动福利';
        foreach ($sendActivity as $new) {
            $content .= "<br><br> <a href='{$new['url']}'>【{$new['time']}】 {$new['subject']}</a><br>{$new['reward']}<br>";
        }

        $log['send_mail_res'] = sendMail($subject, $content);
        $log['subject'] = $subject;
        Log::info('Send Dnf Activity', $log);
    }

    /**
     * tieba news
     *
     * https://tieba.baidu.com/f?kw=dnf%E6%90%AC%E7%A0%96&ie=utf-8
     *
     * @return void
     */
    public function tiebaNews()
    {
        $baseUri = 'https://tieba.baidu.com';
        // 获取最新官网信息
        try {
            $indexDoc = new Document($this->tiebaUrl, true);
        } catch(Exception $e) {
            Log::error('Get DNF tieba news error.', $e->getMessage());
            return;
        }
        $list = $indexDoc->find('#thread_list .j_thread_list');

        // 公告、活动
        $tiezi = [];
        foreach ($list as $li) {
            $a = $li->first('.threadlist_title')->first('a');
            $c = $li->first('.threadlist_text');
            $tie['title'] = trim($a->text());
            $tie['href'] = path($baseUri, $a->attr('href'));
            $tie['content'] = $c ? trim($c->text()) : '';
            $tie['content'] = $tie['content'] == $tie['title'] ? '' : $tie['content'];
            $tiezi[] = $tie;
        }

        // 发送邮件
        $log = [];
        $subject = 'DNF搬砖吧看看';
        $content = '';
        foreach ($tiezi as $tie) {
            $content .= "<br><br> <a href='{$tie['href']}'>{$tie['title']}</a><br>"
                . ($tie['content'] == '' ? '' : "{$tie['content']}<br>");
        }

        $log['send_mail_res'] = sendMail($subject, $content);
        $log['subject'] = $subject;
        Log::info('Send Dnf tieba news.', $log);
    }
}

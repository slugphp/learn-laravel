<?php

namespace App\Models;

use Cache;
use Log;
use Storage;
use Mail;
use GuzzleHttp\Client as Http;

class Lantern
{
    protected $commitsUrl = 'https://api.github.com/repos/getlantern/lantern-binaries/commits';
    protected $exeUrl = 'https://raw.githubusercontent.com/getlantern/lantern-binaries/master/lantern-installer.exe';
    protected $dmgUrl = 'https://raw.githubusercontent.com/getlantern/lantern-binaries/master/lantern-installer.dmg';
    // protected $apkUrl = 'https://raw.githubusercontent.com/getlantern/lantern-binaries/master/lantern-installer-beta.apk';

    function __construct()
    {
        Log::getMonolog()->popHandler();
        Log::useFiles('storage/logs/lantern.log');
    }

    /**
     * 网络好的地方执行
     * 将lantern.exe & apk 发送到邮箱
     * 以供网络不好的地方使用
     */
    public function checkUpdate()
    {
        // 获取缓存
        $key = 'lantern_last_commit_cache';
        $oldCommits = Cache::get($key);

        // 获取最新commit
        $http = new Http();
        $res = (string) $http->get($this->commitsUrl)->getBody();
        $lastCommits = json_decode($res, true)[0];

        if (empty($lastCommits)) {
            Log::info('SendLanternEmail Get commit error', [$res]);
            return;
        }
        if ($oldCommits['sha'] == $lastCommits['sha']) {
            Log::info('SendLanternEmail No commit update', $lastCommits['commit']);
            return;
        }

        // 保存附件
        $exeFile = "tmpfile/{$lastCommits['sha']}.exe";
        Storage::put($exeFile, file_get_contents($this->exeUrl));
        $dmgFile = "tmpfile/{$lastCommits['sha']}.dmg";
        Storage::put($dmgFile, file_get_contents($this->dmgUrl));

        // 发送邮件
        $log = [];
        $subject = $content = $lastCommits['commit']['message'] ?: 'New Lantern';
        $content .= '

        ' . \indentJson([
                $lastCommits['sha'],
                $lastCommits['commit'],
            ]);
        $content .= "\r\n\r\n\r\n" . \indentJson([
                $oldCommits['sha'],
                $oldCommits['commit'],
            ]);

        $log['send_mail'] = Mail::raw(
            $content,
            function ($message) use ($subject, $exeFile, $dmgFile) {
                $message->from('wangweilong2020@163.com', 'Learn-Laravel');
                $message->to('wilonx@163.com', '王伟龙');
                $message->subject($subject);
                $storagePath  = Storage::getDriver()->getAdapter()->getPathPrefix();
                $message->attach("{$storagePath}{$exeFile}");
                $message->attach("{$storagePath}{$dmgFile}");
        });
        $log['subject'] = $subject;
        simpleDump($log);
        Log::info('SendLanternEmail New commit', $log);
        Cache::forever($key, $lastCommits);
    }

    /**
     * https://github.com/getlantern/forum/issues
     * 有新的【公告、精华】就赶紧回复下
     */
    public function checkNewIssues()
    {
        $username = 'getlantern';
        $repository = 'forum';
        $client = new \Github\Client();
        $issue = $client->api('issue')
            ->all($username, $repository, [
                'labels' => '精华,公告'
            ])[0];
        $log['html_url'] = $issue['html_url'];
        $key = 'getlantern-forum';
        echo " Lantern.php ", $issue['html_url'], PHP_EOL;
        if (Cache::get($key) !== $issue['html_url']) {
            Cache::forever($key, $issue['html_url']);
            // issue
            $client->authenticate(
                env('GITHUB_USERNAME'), env('GITHUB_PASSWORD')
            );
            $res = $client->api('issue')
                ->comments()
                ->create($username, $repository, $issue['number'], [
                    'body' => <<<EOF
支持！

```diff
+ 输入我的邀请码 YMD8TTG 来获得三个月的蓝灯专业版！
- 输入我的邀请码 YMD8TTG 来获得三个月的蓝灯专业版！
```
EOF
                ]);
            // email
            $subject = 'GetLantern-Forum 有新的精华or公告';
            $log['send_mail_res'] = Mail::raw(
                $issue['html_url'],
                function ($message) use ($subject) {
                    $message->from('wangweilong2020@163.com', 'Learn-Laravel');
                    $message->to('wilonx@163.com', '王伟龙');
                    $message->subject($subject);
            });
            Log::info("New comment add.", $log);
        } else {
            Log::info("Have not new issue.", $log);
        }
    }
}

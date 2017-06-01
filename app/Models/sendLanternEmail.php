<?php

namespace App\Models;

use Cache;
use Log;
use Storage;
use Mail;
use GuzzleHttp\Client as Http;

/**
 * 网络好的地方执行
 * 将lantern.exe & apk 发送到邮箱
 * 以供网络不好的地方使用
 */
class sendLanternEmail
{
    protected $commitsUrl = 'https://api.github.com/repos/getlantern/lantern-binaries/commits';
    protected $exeUrl = 'https://raw.githubusercontent.com/getlantern/lantern-binaries/master/lantern-installer-beta.exe';
    // protected $dmgUrl = 'https://raw.githubusercontent.com/getlantern/lantern-binaries/master/lantern-installer-beta.dmg';
    // protected $apkUrl = 'https://raw.githubusercontent.com/getlantern/lantern-binaries/master/lantern-installer-beta.apk';

    function check()
    {
        // 获取缓存
        $key = 'lantern_last_commit';
        $oldCommits = Cache::get($key);

        // 获取最新commit
        $http = new Http();
        $res = (string) $http->get($this->commitsUrl)->getBody();
        $lastCommits = json_decode($res, true)[0];

        if (empty($lastCommits)) {
            Log::info('sendLanternEmail Get commit error', [$res]);
            return;
        }
        if ($oldCommits['sha'] == $lastCommits['sha']) {
            Log::info('sendLanternEmail No commit', $lastCommits['commit']);
            return;
        }

        // 发送邮件
        $exeFile = "tmpfile/{$lastCommits['sha']}.exe";
        $log['down_exe'] = file_get_contents($this->exeUrl);
        Storage::put($exeFile, $log['down_exe']);
        $subject = $content = $lastCommits['commit']['message'] ?: 'New Lantern';
        $content .= '<br><br><br><br>' . \indentToJson([
                $lastCommits['sha'],
                $lastCommits['commit'],
            ]);
        $content .= '<br><br><br><br>' . \indentToJson([
                $oldCommits['sha'],
                $oldCommits['commit'],
            ]);

        $log['send_mail'] = Mail::raw(
            $content,
            function ($message) use ($subject, $exeFile) {
                $message->from('973885303@qq.com', 'Learn-Laravel');
                $message->to('wilonx@163.com', '王伟龙');
                $message->subject($subject);
                $storagePath  = Storage::getDriver()->getAdapter()->getPathPrefix();
                $message->attach("{$storagePath}{$exeFile}");
        });
        $log['subject'] = $subject;
        Log::info('sendLanternEmail New commit', $log);
        Cache::forever($key, $lastCommits);
    }

}

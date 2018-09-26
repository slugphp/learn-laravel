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
    protected $apkUrl = 'https://raw.githubusercontent.com/getlantern/lantern-binaries/master/lantern-installer-beta.apk';

    // 邀请码
    protected $code = 'YJMMFJ3';

    // 官方论坛
    protected $githubUsername = 'getlantern';
    protected $githubRepo = 'lantern';

    // GitHub API
    protected $githubClient;
    protected $githubIssueClient;

    function __construct()
    {
        Log::getMonolog()->popHandler();
        Log::useFiles(base_path('storage/logs/lantern.log'));

        // GitHub API
        $this->githubClient = new \Github\Client();
        $this->githubClient->authenticate(
            env('GITHUB_USERNAME'), env('GITHUB_PASSWORD')
        );
        $this->githubIssueClient = $this->githubClient->api('issue');

    }

    /**
     * 网络好的地方执行
     * 将lantern.exe & apk 发送到邮箱
     * 以供网络不好的地方使用
     */
    public function checkUpdate()
    {
        // 获取缓存
        $key = 'lantern_last_commit_cache2';
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
        $apkFile = "tmpfile/{$lastCommits['sha']}.apk";
        Storage::put($apkFile, file_get_contents($this->apkUrl));

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

        $storagePath  = Storage::getDriver()->getAdapter()->getPathPrefix();
        $attachArr = [
            $storagePath . $exeFile,
            $storagePath . $dmgFile,
            $storagePath . $apkFile,
        ];
        $log['send_mail'] = sendMail($subject, $content, $attachArr);
        $log['subject'] = $subject;
        Log::info('SendLanternEmail New commit', $log);
        Cache::forever($key, $lastCommits);
    }

    /**
     * https://github.com/getlantern/lantern/issues
     *
     * 有新的【公告、精华】就赶紧回复下
     */
    public function checkNewIssues()
    {
        // 获取最新的精华、公告
        $issues = $this->githubIssueClient
            ->all($this->githubUsername, $this->githubRepo, [
                'labels' => '精华,公告'
            ])[0];
        $log['html_url'] = $issues['html_url'];
        $key = 'getlantern-lantern';
        echo " Lantern.php ", $issues['html_url'], PHP_EOL;

        // 没发过就赶紧抢二楼
        if (Cache::get($key) !== $issues['html_url']) {
            Cache::forever($key, $issues['html_url']);
            $commit = <<<EOF
支持！

```diff
+ 输入我的邀请码 {$this->code} 来获得三个月的蓝灯专业版！
- 输入我的邀请码 {$this->code} 来获得三个月的蓝灯专业版！
```
EOF;
            // issue
            $res = $this->githubIssueClient
                ->comments()
                ->create($username, $repository, $issues['number'], [
                    'body' => $commit
                ]);
            // email
            $subject = 'GetLantern-lantern 有新的精华or公告';
            $log['send_mail_res'] = sendMail($subject, $issues['html_url']);
            Log::info("New comment add.", $log);
        } else {
            Log::info("Have not new issue.", $log);
        }
    }

    /**
     * getlantern/forum 挂了
     * 改去 getlantern/lantern/issues 下发帖
     * 7点后发帖，20点前关闭
     *
     * @return
     */
    public function checkNewAd()
    {
        // 获取第一页 issue
        $issues = $this->githubIssueClient->all(
            $this->githubUsername, $this->githubRepo
        );

        // issue 内容
        $adArr = [];
        $ad0 = [
            'title' => '出租授权，每月10元；加wx：wait_gale',
            'body'  => <<<EOF
10块钱你买不了吃亏买不了上当~
购买之后长期提供技术支持，本人程序员，Mac、Win、Ubuntu、安卓等都能搞定。
iOS别问了，还没出没法用。

------
![image](https://user-images.githubusercontent.com/25633544/41326243-c15b201a-6ef0-11e8-89cd-7ca2dfbcfdcb.png)

EOF
        ];
        $ad1 = [
            'title' => "充值时输入邀请码 {$this->code} 可额外获赠3个月蓝灯专业版！",
            'body'  => <<<EOF
```diff
+ 充值时输入邀请码 {$this->code} 可额外获赠3个月蓝灯专业版！
- 充值时输入邀请码 {$this->code} 可额外获赠3个月蓝灯专业版！
```
EOF
        ];

        // $adArr[] = $ad0;
        $adArr[] = $ad1;
        // 发送
        foreach ($adArr as $ad) {

            // 判断第一页发过没
            $issueNumber = -1;
            foreach ($issues as $issue) {
                if ($issue['title'] == $ad['title']) {
                    Log::info("Already has issue ad: {$issue['url']} {$issue['title']}");
                    $issueNumber = $issue['number'];
                    break;
                }
            }

            // 发过了且过了晚上20点
            if ($issueNumber > 0 && date('H') > 20) {
                // 关闭issue
                $res = $this->githubIssueClient->update(
                    $this->githubUsername, $this->githubRepo,
                    $issueNumber,
                    array('state' => 'closed')
                );
                Log::info("{$res['closed_by']['login']} Close ad: {$ad['title']}");
            }
            // 没发过且在[7点~20点]之间
            if ($issueNumber < 0 && date('H') > 7 && date('H') < 20) {
                // 发广告喽~
                $res = $this->githubIssueClient->create(
                    $this->githubUsername, $this->githubRepo, $ad
                );
                Log::info("New ad: {$res['url']} {$res['title']}");
            }
        }
    }
}

<?php

/**
 * 2333
 *
 * @author weilong <wilonx@163.com>
 */

/**
 * 发送邮件
 *
 * @param  string $subject  标题
 * @param  string $content  内容，支持HTML
 * @param  array  $attacArr 附件
 * @return boolean
 */
function sendMail($subject = '', $content = '', $attacArr = [])
{
    return \Mail::send(
        'mailbody',
        ['body' => $content],
        function ($message) use ($subject) {
            $message->from('wangweilong2020@163.com', 'no-reply');
            $message->to('973885303@qq.com', '王伟龙');
            $message->subject($subject);
            foreach ($attacArr as $attach) {
                $message->attach($attach);
            }
        }
    ) == 1;
}

function cnWeek() {
    return '星期' . ['日', '一', '二', '三', '四', '五', '六'][date('w')];
}
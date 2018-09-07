<?php

function send_mail($subject = '', $content = '')
{
    return \Mail::send(
        'mailbody',
        ['body' => $content],
        function ($message) use ($subject) {
            $message->from('wangweilong2020@163.com', 'Weilong的自动提醒');
            $message->to('wilonx@163.com', '王伟龙');
            $message->subject($subject);
    }) == 1;
}
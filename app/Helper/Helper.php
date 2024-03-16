<?php

namespace App\Helper;

class Helper
{
    public static function send_tg_msg($msg, $tgid) {
        $token=config('tg.tg_token');
        $groupId=config('tg.tg_group_id');
        print($token);
        $msg = urlencode("@$tgid $msg");
        $msg = "https://api.telegram.org/bot$token/sendMessage?chat_id=$groupId&text=$msg";
        // $msg = "https://api.telegram.org/bot6093249444:AAFUWmQYGN5-kS3BN0v3TweMsTezfG5qvwA/sendMessage?chat_id=6866987402&text=hello%2C+testing
        // ";
    
        file_get_contents($msg);
    }
}
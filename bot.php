<?php

require_once __DIR__.'/config.inc.php';

include __DIR__.'/vendor/autoload.php';

use Discord\Discord;
use Discord\WebSockets\WebSocket;

$discord = new Discord($BOT_TOKEN);
$ws      = new WebSocket($discord);

$ws->on('ready', function ($discord) use ($ws) {
    echo "Bot is ready!".PHP_EOL;

    // We will listen for messages
    $ws->on('message', function ($message, $discord) {
        $show_me_command = '\\show-me ';
        if (0 !== stripos($message->content, '\\show-me ')) {
            //We have nothing to do here
            return;
        }
        
        $search = str_replace($show_me_command, '', $message->content);
        
        //Send a search
        if (!$json = file_get_contents('http://imgur.com/search.json?q='.urlencode($search))) {
            return;
        }
        
        if (!$json = json_decode($json, true)) {
            return;
        }
        
        if (!isset($json['data']) || empty($json['data'])) {
            $message->reply('Nothing there Sir.');
            return;
        }

        $rand_key = array_rand($json['data'], 1);
        $image = $json['data'][$rand_key];
        
        $image_url = 'http://imgur.com/' . $image['hash'] . $image['ext'];
        
        $message->reply('Sir, here is the image that you requested. ' . $image_url);
    });
});

$ws->run();

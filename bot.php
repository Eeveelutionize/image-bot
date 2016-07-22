<?php

require_once __DIR__.'/config.inc.php';

include __DIR__.'/vendor/autoload.php';

use Discord\Discord;
use Discord\WebSockets\WebSocket;

$discord = new Discord($BOT_TOKEN);
$ws      = new WebSocket($discord);

$ws->on('ready', function ($discord) use ($ws) {
    echo date("Y-m-d H:i:s") . " -- Bot is ready!".PHP_EOL;
    $requests = [];

    // We will listen for messages
    $ws->on('message', function ($message, $discord) use (&$requests) {
        if (1 === rand(1,420)) {
            $message->reply('same');
            return;
        }

        $rate_limit = 3;
        $rate_limit_length = 5*60;
        
        $show_me_command = '\\show-me ';
        if (0 !== stripos($message->content, '\\show-me ')) {
            //We have nothing to do here
            return;
        }
        
        $time = time();
        
        $key = $message->author->username;
        if (!isset($requests[$key][$time])) {
            $requests[$key][$time] = 0;
        }
        
        //increase the number of requests
        $requests[$key][$time]++;

        //Create a new array removing times outside of limit length.
        $new_array = array();
        array_walk($requests[$key], function($val, $key) use (&$new_array, $time, $rate_limit_length) {
            if ($key >= ($time-$rate_limit_length)) {
                $new_array[$key] = $val;
            }
        });
        
        //Now replace the original array
        $requests[$key] = $new_array;
        
        if (array_sum($requests[$key]) > $rate_limit) {
            $message->reply('Sorry, I can\'t do that, Sir.');
            return;
        }
        
        $search = str_replace($show_me_command, '', $message->content);
        
        $search_method = rand(0,1);

        $image_url = false;

        if ('nsfw' == $search) {
            $message->reply('no');
            return;
        }
        
        switch ($search_method) {
            case 0:
                //Send a search
                if (!$json = file_get_contents('http://imgur.com/search.json?q='.urlencode($search))) {
                    break;
                }

                if (!$json = json_decode($json, true)) {
                    break;
                }

                if (isset($json['data']) && !empty($json['data'])) {
                    $rand_key = array_rand($json['data'], 1);
                    $image = $json['data'][$rand_key];
                    $image_url = 'http://imgur.com/' . $image['hash'] . $image['ext'];
                }
                break;
            case 1:
                $giphy = new \rfreebern\Giphy();
                $result = $giphy->random($search);
                if ($result && !empty($result->data)) {
                    $image_url = $result->data->image_original_url;
                }
                break;
        }
        
        if (!$image_url) {
            $message->reply('No image to be found, Sir.');
        } else {
            $message->reply('Sir, here is the image that you requested. ' . $image_url);
        }
    });
});

$ws->on(
    'error',
    function ($error, $ws) {
        dump($error);
        exit(1);
    }
);

$ws->run();

exit(1); //always trigger a demon restart


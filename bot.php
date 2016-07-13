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
        $rate_limit = 3;
        $rate_limit_length = 5*60;

        $requests = [];
        
        $show_me_command = '\\show-me ';
        if (0 !== stripos($message->content, '\\show-me ')) {
            //We have nothing to do here
            return;
        }
        
        $time = time();
        
        if (!isset($requests[$time])) {
            $requests[$time] = 0;
        }
        
        //increase the number of requests
        $requests[$time]++;

        //Create a new array removing times outside of limit length.
        $new_array = array();
        array_walk($requests, function($val, $key) use (&$new_array, $time, $rate_limit_length) {
            if ($key >= ($time-$rate_limit_length)) {
                $new_array[$key] = $val;
            }
        });
        
        //Now replace the original array
        $requests = $new_array;
        
        if (array_sum($requests) >= $rate_limit) {
            $message->reply('Sorry, I can\'t do that, Sir.');
            return;
        }
        
        $search = str_replace($show_me_command, '', $message->content);
        
        $search_method = rand(0,1);

        $image_url = false;
        
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

$ws->run();

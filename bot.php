<?php

require_once __DIR__.'/config.inc.php';

include __DIR__.'/vendor/autoload.php';

use Discord\Discord;
use Discord\WebSockets\WebSocket;
use \Discord\Parts\Channel\Message;

$discord = new Discord($BOT_TOKEN);
$ws      = new WebSocket($discord);

function same(Message $message) {
    if (420 === rand(1,420)) {
        $message->reply('same');
        return true;
    }

    return false;
}

function rateLimit(Message $message, &$requests) {
    $rate_limit = 3;
    $rate_limit_length = 5*60;

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
        return true;
    }

    return false;
}

function showMe(Message $message, $query) {
    $search_method = rand(0,1);

    $image_url = false;

    if (false !== stripos($query, 'nsfw')) {
        $message->reply('no');
        return true;
    }

    switch ($search_method) {
        case 0:
            //Send a search
            if (!$json = file_get_contents('http://imgur.com/search.json?q='.urlencode($query))) {
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
            $result = $giphy->random($query);
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

    return true;
}

function sentiment(Message $message, $query) {
    $sentiment = new \PHPInsight\Sentiment();

    $category = $sentiment->categorise($query);
    $score = $sentiment->score($query);

    $reply = 'I am ' . $score[(string)$category]*100 . '% sure that "'.$query.'" is ';

    switch ($category) {
        case 'neg':
            $reply .= 'salty';
            break;
        case 'neu':
            $reply .= '¯\_(ツ)_/¯';
            break;
        case 'pos':
            $reply .= 'dank';
            break;
    }

    $reply .= '.';

    $message->reply($reply);

    return true;
}

$ws->on('ready', function ($discord) use ($ws) {
    echo date("Y-m-d H:i:s") . " -- Bot is ready!".PHP_EOL;

    $requests = [];
    $lastMessage = null;
    // We will listen for messages
    $ws->on('message', function ($message, $discord) use (&$requests, &$lastMessage) {
        /**
         * @var \Discord\Parts\Channel\Message $message
         */
        if (same($message)) {
            $lastMessage = $message;
            return;
        }

        $commands = [
            '\\show-me' => 'showMe',
            '\\sentiment' => 'sentiment'
        ];

        $parts = explode(' ', $message->content);
        $command = $parts[0];

        if (isset($commands[$command])) {
            //This is a command for this bot.
            if (rateLimit($message, $requests)) {
                $lastMessage = $message;
                return;
            }

            //Execute the command
            $query = str_replace($command.' ', '', $message->content);
            $query = trim($query);
            if (empty($query) && $lastMessage) {
                //Default to the last message content
                $query = $lastMessage->content;
            }
            $commands[$command]($message, $query);
        }

        $lastMessage = $message;
    });
});

$ws->on(
    'error',
    function ($error, $ws) {
        print_r($error);
        exit(1);
    }
);

$ws->run();

exit(1); //always trigger a demon restart


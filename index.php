<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
echo '<pre>';
//date_default_timezone_set('GMT');

@mkdir(__DIR__ . '/tmp');

require('app/TwitterBot.php');
require('app/RSSReader.php');
require('app/file_lock.php');

$conf = require('conf.php');


//header('Content-Type: text/html; charset=utf-8');

/*


$twitter = new TwitterBot('<Consumer_key>', '<Consumer_secret>');
$twitter->setToken('<Access_token>', '<Access_token_secret>');

$twitter->test();

*/

$twitters = [];

$dotenv = Dotenv::create(__DIR__);
$dotenv->load();

$dotenv->required('ACCOUNTS');

$accountsKey = $_ENV['ACCOUNTS'];

$accounts = explode(',', $accountsKey);

foreach($accounts as $account) {
    //# Account name + _API_KEY|_API_SECRET|_ACCESS_TOKEN|_ACCESS_SECRET
    $key = strtoupper($account);
    $dotenv->required(["${key}_API_KEY", "${key}_API_SECRET", "${key}_ACCESS_TOKEN", "${key}_ACCESS_SECRET"]);
    $twitter = new TwitterBot($_ENV["${key}_API_KEY"], $_ENV["${key}_API_SECRET"]);
    $twitter->setToken($_ENV["${key}_ACCESS_TOKEN"], $_ENV["${key}_ACCESS_SECRET"]);

    $twitters[$account] = $twitter;
}

foreach($conf as $account => $confLine) {

    if(!isset($twitters[$account])) {
        continue;
    }
    if(isset($confLine['rss'])) {
        foreach($confLine['rss'] as $rss) {
            $lock = getSinceId('lock-rss-' . $account, 'DateTime');
            $feed = getFeed($rss, new DateTime($lock));

            foreach($feed['data'] as $data) {
                $twitters[$account]->addTweet($data['url'], $data['text']);
            }

            setSinceId('lock-rss-' . $account, $feed['last']->format('Y-m-d H:i'));
            //;
        }

    }

    if(isset($confLine['retweet'])) {
        foreach($confLine['retweet'] as $retweet) {
            $twitters[$account]->addRetweetAccount($retweet);
        }

    }
}

foreach($twitters as $twitter) {
    $twitter->run();
}

//var_dump($twitters);


//$twitters['leomaradan']->run();

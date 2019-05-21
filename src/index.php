<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

//date_default_timezone_set('GMT');

require('TwitterBot.php');
require('RSSReader.php');

$conf = require('conf.php');



foreach($conf as $confLine) {
    if(isset($confLine['rss'])) {
        getFeed($confLine['rss'][0]);
        die;
    }
}

//header('Content-Type: text/html; charset=utf-8');

/*


$twitter = new TwitterBot('<Consumer_key>', '<Consumer_secret>');
$twitter->setToken('<Access_token>', '<Access_token_secret>');

$twitter->test();

*/

$twitters = [];

$dotenv = Dotenv::create(__DIR__ . '/..');
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

$twitters['leomaradan']->addRetweetAccount('mcradane');
$twitters['leomaradan']->run();

<?php

require __DIR__ . '/vendor/autoload.php';

date_default_timezone_set('GMT');

require('app/options.php');
require('app/TwitterBot.php');
require('app/RSSReader.php');
require('app/file_lock.php');
require('app/logger.php');

if($options['html']) {
    echo '<pre>';
}

if($options['help']) {
    //"help" => false,
   /* "verbose" => false,
    "simulation" => true,
    //"accounts" => null,
    "html" => true,*/
    echo 'Command line options:' . PHP_EOL . PHP_EOL;
    echo 'php daemon.php --verbose => Display debug message.' . PHP_EOL;
    echo 'php daemon.php --simulation => run the process in simulation mode. Implie verbose.' . PHP_EOL;
    echo 'php daemon.php --html => Display <pre> in start of the document. Usefull for verbose mode in browser.' . PHP_EOL;
    echo 'php daemon.php --help => This help command.' . PHP_EOL . PHP_EOL;
    echo 'Browser option:' . PHP_EOL . PHP_EOL;
    echo 'all options can be used is browser context' . PHP_EOL;
    echo 'eg.: daemon.php?simulation=true&html=false' . PHP_EOL;
    die;
}

@mkdir(__DIR__ . '/tmp');

$log = new Logger();

$conf = json_decode(json_encode(require('conf.php')), FALSE);

// Validate
$validator = new JsonSchema\Validator;
$validator->validate($conf, (object)['$ref' => 'file://' . realpath('schema.json')]);

if (!$validator->isValid()) {
    $message = "The configuration does not validate. Violations:\n";
    foreach ($validator->getErrors() as $error) {
        $message .= sprintf("[%s] %s\n", $error['property'], $error['message']);
    }
    throw new Exception($message);
}

$twitters = [];

foreach($conf as $confLine) {

    $twitter = new TwitterBot($confLine->account, $confLine->tokens->api_key, $confLine->tokens->api_secret);
    $twitter->setToken($confLine->tokens->access_token, $confLine->tokens->access_secret);

    $twitters[$confLine->account] = $twitter;

    if(isset($confLine->rss)) {
        foreach($confLine->rss as $rss) {
            $lock = getSinceId('lock-rss-' . $confLine->account, 'DateTime');
            if(is_string($rss)) {
                $feed = getFeed($rss, new DateTime($lock));

                foreach($feed->data as $data) {
                    $twitters[$confLine->account]->addTweet($data->url, $data->text, $data-hashtag);
                }
            } else {
                $parser = ($rss->parser) ?: null;
                $feed = getFeed($rss->url, new DateTime($lock), $parser);

                foreach($feed->data as $data) {

                    $filter_input = ($rss->filter_hashtag_input) ?: [];
                    $filter_output = ($rss->filter_hashtag_output) ?: [];

                    $twitters[$confLine->account]->addTweet($data->url, $data->text, $data->hashtag, $rss->include_permalink, $rss->include_hashtags, $filter_input, $filter_output);
                }
            }

            if(!$_ENV['simulation']) {
                setSinceId('lock-rss-' . $confLine->account, $feed->last->format('Y-m-d H:i'));
            }
        }

    }

    if(isset($confLine->retweet)) {
        foreach($confLine->retweet as $retweet) {
            if(is_string($retweet)) {
                $twitters[$confLine->account]->addRetweetAccount($retweet);
            } else {
                $twitters[$confLine->account]->addRetweetAccount($retweet->screen, !!$retweet->response, !!$retweet->retweet);
            }
        }
    }
}

foreach($twitters as $twitter) {
    $twitter->run();
}

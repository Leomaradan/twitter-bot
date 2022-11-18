<?php

use \Noweh\TwitterApi\Client;

const MAX_CHAR = 280;

class TwitterBot
{

    private $screenName;
    private $shorturl_length;

    private $retweetAccount;
    private $tweet;

    private $client;

    public function __construct($account_id, $api_key, $api_secret, $bearer_token, $access_token, $access_secret)
    {
        $settings = [
            'account_id' => $account_id,
            'consumer_key' => $api_key,
            'consumer_secret' => $api_secret,
            'bearer_token' => $bearer_token,
            'access_token' => $access_token,
            'access_token_secret' => $access_secret
        ];

        $this->client = new Client($settings);

        $this->screenName = $this->client->userSearch()->findByIdOrUsername($account_id)->performRequest()->data->username;

        $this->retweetAccount = [];
        $this->tweet = [];
    }

    public function addRetweetAccount($screen, $response = false, $retweet = true)
    {
        logDebug("ADD Retweet Account $screen for source account " . $this->screenName);
        $this->retweetAccount[] = (object)compact("screen", "response", "retweet");
    }

    public function addTweet($url, $text, $hashtags, $include_permalink = true, $include_hashtags = false, $filter_input = [], $filter_output = [])
    {
        foreach ($filter_input as $filter) {
            if (in_array($filter, $hashtags)) {
                logDebug('REJECTING TWEET : Output contains hashtag ' . $filter);
                return;
            }
        }

        foreach ($filter_output as $filter) {
            if (in_array($filter, $hashtags)) {
                $key = array_search($filter, $hashtags);
                unset($hashtags[$key]);
            }
        }

        $data = ['text' => $text];

        if ($include_permalink) {
            $data['url'] = $url;
        }

        if ($include_permalink) {
            $data['hashtags'] = $hashtags;
        }

        $this->tweet[] = $data;
    }

    public function run()
    {
        $this->retweet();
        $this->sendTweet();
    }

    public function sendTweet()
    {
        logDebug("START SEND_TWEET");

        foreach ($this->tweet as $tweet) {
            try {
                $text = $tweet['text'];
                $length = strlen($text) + 1;

                if (isset($tweet['url'])) {
                    $length -= $this->shorturl_length;
                }

                if (isset($tweet['hashtags'])) {
                    foreach ($tweet['hashtags'] as $hashtag) {
                        $length -= strlen($hashtag) + 2;
                    }
                }

                if ($length > MAX_CHAR) {
                    $text = $this->elegantHyphenation($text, isset($tweet['url']), $tweet['hashtags']);
                }

                $array = ['text' => $text];

                if (isset($tweet['hashtags'])) {
                    foreach ($tweet['hashtags'] as $hashtag) {
                        $array['text'] .= ' #' . $hashtag;
                    }
                }

                if (isset($tweet['url'])) {
                    $array['text'] .= ' ' . $tweet['url'];
                }

                if (!$_ENV['simulation']) {
                    $this->client->tweet()->performRequest('POST', $array);
                    logInfo("SEND " . $array['text']);
                } else {
                    logDebug('Simulation: POST ' . htmlentities($array['text']));
                }
            } catch (Exception $ex) {
                logError(var_export($ex));
            }
        }

        logDebug("DONE SEND_TWEET");
    }

    public function retweet()
    {
        logDebug("START RETWEET");
        $since_id = getSinceId('lock-retweet-' . $this->screenName);

        $max_id = $since_id;

        $userNames = [];

        foreach ($this->retweetAccount as $key => $retweetAccount) {
            $userNames[] = $retweetAccount->screen;
        }


        $tweets = $this->client->tweetSearch()->showReferencedTweets()->showUserDetails()->addFilterOnUsernamesFrom($userNames)->performRequest();

        foreach ($tweets->data as $tweet) {

            $isRetweet = false;
            $isResponseOrQuote = false;

            if (isset($tweet->referenced_tweets)) {
                foreach ($tweet->referenced_tweets as $referenced_tweet) {
                    if ($referenced_tweet->type == 'replied_to' || $referenced_tweet->type == 'quoted') {
                        $isResponseOrQuote = true;
                    }

                    if ($referenced_tweet->type == 'retweeted') {
                        $isRetweet = true;
                    }
                }
            }


            if (!$retweetAccount->response) {
                if ($isResponseOrQuote) { // referenced_tweets.id
                    continue;
                }
            }

            if (!$retweetAccount->retweet) {
                if ($isRetweet) { // data.referenced_tweets.id (if type=retweeted)
                    continue;
                }
            }

            if ($tweet->id > $max_id) {
                $max_id = $tweet->id;
            }

            if (!$_ENV['simulation']) {
                $this->client->retweet()->performRequest('POST', ['tweet_id' => $tweet->id]);
                logInfo("RT " . $tweet->text);
            } else {
                logDebug('Simulation: RT ' . $tweet->id);
            }
        }

        if (!$_ENV['simulation']) {
            /* setting new max id */
            setSinceId('lock-retweet-' . $this->screenName, $max_id);
        }

        logDebug("END RETWEET");
    }

    private function elegantHyphenation($string, $useUrl, $hashtags)
    {
        $max = MAX_CHAR - 5;

        if ($useUrl) {
            $max -= $this->shorturl_length;
        }

        if (isset($hashtags)) {
            foreach ($hashtags as $hashtag) {
                $max -= strlen($hashtag) + 2;
            }
        }

        $words = explode(' ', $string);

        $output = '';
        $count = 0;

        foreach ($words as $word) {
            $wordLength = strlen($word) + 1;

            if ($count + $wordLength <= $max) {
                $output .= ' ' . $word;
                $count += $wordLength;
            }
        }

        return trim($output) . ' ...';
    }
}

<?php

const MAX_CHAR = 280;

class TwitterBot
{
    protected $url_update = 'https://api.twitter.com/1.1/statuses/update.json';
    protected $url_verify = 'https://api.twitter.com/1.1/account/verify_credentials.json';
    protected $user_timeline = 'https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name=%s';
    protected $url_retweet = 'https://api.twitter.com/1.1/statuses/retweet/%s.json';
    protected $url_config = 'https://api.twitter.com/1.1/help/configuration.json';

    private $oauth;
    private $screenName;
    private $shorturl_length;

    private $retweetAccount;
    private $tweet;

    public function __construct($account, $key, $secret)
    {
        $this->oauth = new OAuth($key, $secret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
        $this->oauth->disableSSLChecks();
        $this->screenName = $account;

        $this->retweetAccount = [];
        $this->tweet = [];
    }

    public function setToken($token, $secret)
    {
        $this->oauth->setToken($token, $secret);
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
        if ($this->verifyAccountWorks()) {
            $this->retweet();
            $this->sendTweet();
        }
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

                $array = [ 'status' => $text ];

                if (isset($tweet['hashtags'])) {
                    foreach ($tweet['hashtags'] as $hashtag) {
                        $array['status'] .= ' #' . $hashtag;
                    }
                }

                if (isset($tweet['url'])) {
                    $array['status'] .= ' ' . $tweet['url'];
                }

                if (!$_ENV['simulation']) {
                    $this->oauth->fetch($this->url_update, $array, OAUTH_HTTP_METHOD_POST);
                    logInfo("SEND " . $array['status']);
                } else {
                    logDebug('Simulation: fetch '.$this->url_update.' '.htmlentities($array['status']).' '.OAUTH_HTTP_METHOD_POST);
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

        foreach ($this->retweetAccount as $key => $retweetAccount) {
            $url = sprintf($this->user_timeline, $retweetAccount->screen);
            $this->oauth->fetch($url);
            $tweets = json_decode($this->oauth->getLastResponse());
            if ($tweets) {
                foreach ($tweets as $tweet) {
                    if (!$retweetAccount->response) {
                        if ($tweet->in_reply_to_status_id != null) {
                            continue;
                        }
                    }

                    if (!$retweetAccount->retweet) {
                        if (isset($tweet->retweeted_status)) {
                            continue;
                        }
                    }

                    if ($tweet->id > $max_id) {
                        $max_id = $tweet->id;
                    }

                    $date = new DateTime($tweet->created_at);

                    $oneMonth = new DateTime();
                    $oneMonth->sub(new DateInterval('P1M'));

                    if ($date > $oneMonth) {
                        if (!$tweet->retweeted) {
                            $url_retweet = sprintf($this->url_retweet, $tweet->id);
                            if (!$_ENV['simulation']) {
                                $this->oauth->fetch($url_retweet, array(), OAUTH_HTTP_METHOD_POST);
                                logInfo("RT ".$tweet->text);
                            } else {
                                logDebug('Simulation: fetch '.$url_retweet.' [] '.OAUTH_HTTP_METHOD_POST);
                            }
                        }
                    }
                }
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

    private function verifyAccountWorks()
    {
        try {
            $this->oauth->fetch($this->url_verify, array(), OAUTH_HTTP_METHOD_GET);
            $response = json_decode($this->oauth->getLastResponse());
            $this->screenName = $response->screen_name;

            $this->oauth->fetch($this->url_config);
            $response = json_decode($this->oauth->getLastResponse());
            $this->shorturl_length = max($response->short_url_length, $response->short_url_length_https);

            return true;
        } catch (Exception $ex) {
            return false;
        }
    }
}

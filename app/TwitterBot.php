<?php

const MAX_CHAR = 280;
//const MAX_CHAR = 140;

class TwitterBot {
    protected $url_update = 'https://api.twitter.com/1.1/statuses/update.json';
    //protected $url_search = 'https://api.twitter.com/1.1/search/tweets.json?q=%s&result_type=recent&count=50&since_id=%s';
    protected $url_verify = 'https://api.twitter.com/1.1/account/verify_credentials.json';
    //protected $url_token = 'https://twitter.com/oauth/request_token';
    //protected $url_token_access = 'https://twitter.com/oauth/access_token';
    //protected $url_auth = 'https://twitter.com/oauth/authorize';
    protected $user_timeline = 'https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name=%s';
    protected $url_retweet = 'https://api.twitter.com/1.1/statuses/retweet/%s.json';
    protected $url_config = 'https://api.twitter.com/1.1/help/configuration.json';

    private $oauth;
    private $screenName;
    private $shorturl_length;

    private $retweetAccount;
    private $tweet;

    public function __construct($key, $secret){
        $this->oauth = new OAuth($key, $secret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
        $this->oauth->disableSSLChecks();

        $this->retweetAccount = [];
        $this->tweet = [];
    }

    public function setToken($token, $secret){
        $this->oauth->setToken($token, $secret);
    }

    public function addRetweetAccount($screen){
        $this->retweetAccount[] = array('screen' => $screen);
    }

    public function addTweet($url, $text) {
        $this->tweet[] = ['url' => $url, 'text' => $text];
    }

    public function run() {
        if ($this->verifyAccountWorks()){
            $this->retweet();
            $this->sendTweet();
        }
    }

    public function sendTweet(){
        foreach($this->tweet as $tweet) {
            try {
                $text = $tweet['text'];
                $length = strlen($text) + 1;

                if($length - $this->shorturl_length > MAX_CHAR) {
                    $text = $this->elegantHyphenation($text);
                }

                $array = array( 'status' => $text . ' ' . $tweet['url'] );
                $this->oauth->fetch($this->url_update, $array, OAUTH_HTTP_METHOD_POST);
                echo date("Y-m-d H:i:s") . " SEND ".$array['status'] . PHP_EOL;
            } catch(Exception $ex) {
                var_dump($ex);
            }
        }

        echo date("Y-m-d H:i:s") . " DONE SEND_TWEET " . PHP_EOL;
    }

    public function retweet(){

        $since_id = getSinceId('lock-retweet-' . $this->screenName);

        $max_id = $since_id;

        foreach ($this->retweetAccount as $key => $t){

            $url = sprintf($this->user_timeline, $t['screen']);
            $this->oauth->fetch($url);
            $tweets = json_decode($this->oauth->getLastResponse());
            if($tweets){

                foreach ($tweets as $tweet){

                    if ($tweet->id > $max_id){
                        $max_id = $tweet->id;
                    }

                    $date = new DateTime($tweet->created_at);

                    $oneMonth = new DateTime();
                    $oneMonth->sub(new DateInterval('P1M'));

                    if($date > $oneMonth) {

                        if(!$tweet->retweeted) {
                            $url_retweet = sprintf($this->url_retweet, $tweet->id);
                            $this->oauth->fetch($url_retweet, array(), OAUTH_HTTP_METHOD_POST);
                            echo date("Y-m-d H:i:s") . " RT ".$tweet->text . PHP_EOL;
                        }
                    }
                }
            }
        }

        /* setting new max id */
        setSinceId('lock-retweet-' . $this->screenName, $max_id);
    }

    private function elegantHyphenation($string) {
        $max = MAX_CHAR - $this->shorturl_length - 5;

        $words = explode(' ', $string);

        $output = '';
        $count = 0;

        foreach($words as $word) {
            $wordLength = strlen($word) + 1;

            if($count + $wordLength <= $max) {
                $output .= ' ' . $word;
                $count += $wordLength;
            }
        }

        return trim($output) . ' ...';
    }

    private function verifyAccountWorks(){
        try{
            $this->oauth->fetch($this->url_verify, array(), OAUTH_HTTP_METHOD_GET);
            $response = json_decode($this->oauth->getLastResponse());
            $this->screenName = $response->screen_name;

            $this->oauth->fetch($this->url_config);
            $response = json_decode($this->oauth->getLastResponse());
            $this->shorturl_length = max($response->short_url_length, $response->short_url_length_https);

            return true;
        }catch(Exception $ex){
            return false;
        }
    }
}
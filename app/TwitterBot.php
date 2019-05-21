<?php

class TwitterBot {
    protected $url_update = 'https://api.twitter.com/1.1/statuses/update.json';
    protected $url_search = 'https://api.twitter.com/1.1/search/tweets.json?q=%s&result_type=recent&count=50&since_id=%s';
    protected $url_verify = 'https://api.twitter.com/1.1/account/verify_credentials.json';
    protected $url_token = 'https://twitter.com/oauth/request_token';
    protected $url_token_access = 'https://twitter.com/oauth/access_token';
    protected $url_auth = 'https://twitter.com/oauth/authorize';
    protected $user_timeline = 'https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name=%s';
    protected $url_retweet = 'https://api.twitter.com/1.1/statuses/retweet/%s.json';

    private $oauth;
    private $screenName;
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
            var_dump($this->tweet);
        }
    }

    public function tweet(){

    }

    public function retweet(){

        $since_id = getSinceId('lock-retweet-' . $this->screenName);


        $max_id = $since_id;

        /* For each request on tweet.php */
        foreach ($this->retweetAccount as $key => $t){
            /* find every tweet since last ID, or the maximum lasts tweets if no since_id */
            $url = sprintf($this->user_timeline, $t['screen']);
            $this->oauth->fetch($url);
            $tweets = json_decode($this->oauth->getLastResponse());
            if($tweets){

                //$i = 0;

                //var_dump($tweets);

                foreach ($tweets as $tweet){

                    if ($tweet->id > $max_id){
                        $max_id = $tweet->id;
                    }

                    $date = new DateTime($tweet->created_at);

                    $oneMonth = new DateTime();
                    $oneMonth->sub(new DateInterval('P1M'));

                    if($date > $oneMonth) {
                        /*$text = (isset($tweet->retweeted_status)) ? $tweet->retweeted_status->text : $tweet->text;

                        $test = [
                            'date' => $date,
                            'id' => $tweet->id,
                            'retweeted' => $tweet->retweeted,
                            'text' => $text,
                            'from' => $tweet->user->screen_name,
                            'account' => $this->screenName
                        ];*/
                        //var_dump($tweet->retweeted);
                        //var_dump($test);
                        if(!$tweet->retweeted) {
                            $url_retweet = sprintf($this->url_retweet, $tweet->id);
                            //var_dump($url_retweet);
                            $this->oauth->fetch($url_retweet, array(), OAUTH_HTTP_METHOD_POST);
                            echo "RT ".$tweet->text . PHP_EOL;
                        }
                    }

                    //var_dump($tweet);
                    //echo '<b><a style="color: red;" href="https://twitter.com/'.$tweet->user->screen_name.'" target="_blank">@'.$tweet->user->screen_name.'</a> :</b> <a style="color: black; text-decoration: none;" href="https://twitter.com/'.$tweet->user->screen_name.'/status/'.$tweet->id.'" target="_blank">'.$tweet->text.'</a>';
                }
            }
        }

        /* setting new max id */
        setSinceId('lock-retweet-' . $this->screenName, $max_id);

    }

    private function verifyAccountWorks(){
        try{
            $this->oauth->fetch($this->url_verify, array(), OAUTH_HTTP_METHOD_GET);
            $response = json_decode($this->oauth->getLastResponse());
            $this->screenName = $response->screen_name;
            return true;
        }catch(Exception $ex){
            return false;
        }
    }
}

/*class TwitterBot2{
    protected $url_update = 'https://api.twitter.com/1.1/statuses/update.json';
    private $oauth;



    public function test(){
        $array = array( 'status' => 'Hello World !' );
         $this->oauth->fetch($this->url_update, $array, OAUTH_HTTP_METHOD_POST);
        }
}*/

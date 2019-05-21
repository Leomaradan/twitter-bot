<?php

class TwitterBot {
    protected $url_update = 'https://api.twitter.com/1.1/statuses/update.json';
    protected $url_search = 'https://api.twitter.com/1.1/search/tweets.json?q=%s&result_type=recent&count=50&since_id=%s';
    protected $url_verify = 'https://api.twitter.com/1.1/account/verify_credentials.json';
    protected $url_token = 'https://twitter.com/oauth/request_token';
    protected $url_token_access = 'https://twitter.com/oauth/access_token';
    protected $url_auth = 'http://twitter.com/oauth/authorize';

    private $oauth;
    private $screenName;
    private $retweetAccount;

    public function __construct($key, $secret){
        $this->oauth = new OAuth($key, $secret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
         $this->oauth->disableSSLChecks();
    }

    public function setToken($token, $secret){
        $this->oauth->setToken($token, $secret);
    }

    public function addRetweetAccount($screen){
        $this->retweetAccount[] = array('screen' => $screen);
    }

    public function run(){

        $since_id = $this->getSinceId();

        $max_id = $since_id;

        if ($this->verifyAccountWorks()){
            /* For each request on tweet.php */
            foreach ($this->retweetAccount as $key => $t){
                /* find every tweet since last ID, or the maximum lasts tweets if no since_id */
                $this->oauth->fetch(sprintf($this->url_search, urlencode('from:' . $t['screen']), $since_id));
                $search = json_decode($this->oauth->getLastResponse());
                if($search){

                    /* Store the last max ID */
                    if ($search->search_metadata->max_id_str > $max_id){
                        $max_id = $search->search_metadata->max_id_str;
                    }

                    $i = 0;
                    foreach ($search->statuses as $tweet){
                        echo '<b><a style="color: red;" href="https://twitter.com/'.$tweet->user->screen_name.'" target="_blank">@'.$tweet->user->screen_name.'</a> :</b> <a style="color: black; text-decoration: none;" href="https://twitter.com/'.$tweet->user->screen_name.'/status/'.$tweet->id.'" target="_blank">'.$tweet->text.'</a>';
                    }
                }
            }

            /* setting new max id */
            $this->setSinceId($max_id);
        }
    }

    public function getSinceId($file='since_id'){
        $since_id = @file_get_contents($file);
        if(!$since_id){
            $since_id = 0;
        }
        return $since_id;
    }

    public function setSinceId($max_id=null,$file='since_id'){
        file_put_contents($file, $max_id);
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

<?php




namespace Example\Extend;


class Twitter {

    private $getTweetCallCount = 0;

    private $pushTweetCallCount = 0;
    
    public $twitterAPIKey;

    public function getTweetCallCount() {
        return $this->getTweetCallCount;
    }

    public function getPushTweetCallCount() {
        return $this->pushTweetCallCount;
    }
    
    public function getTwitterAPIKey() {
        return $this->twitterAPIKey;
    }
    
    function __construct($twitterAPIKey) {
        $this->twitterAPIKey = $twitterAPIKey;
    }
    
    function getTweet($tweetID) {
        $this->getTweetCallCount++;
        return "This is tweet ".$tweetID;
    }
    
    function pushTweet($tweetText) {
        //This function writes to twitter, so is not cacheable
        $this->pushTweetCallCount++;
    }
}
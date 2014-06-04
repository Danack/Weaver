<?php




namespace Example;


class Twitter implements TwitterAPI {

    private $twitterKey;
    
    function __construct($twitterKey) {
        $this->twitterKey = $twitterKey;
    }
    
    function getTweet($tweetID){
        usleep(5);
        return "This is tweet ".$tweetID;
    }
    
    function pushTweet($tweetText) {
        //This function writes to twitter, so is not cacheable
        usleep(10);
    }
}
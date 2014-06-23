<?php




namespace Example;


interface TwitterAPI {
    /**
     * @param $tweetID
     * @return string
     */
    function getTweet($tweetID);
}
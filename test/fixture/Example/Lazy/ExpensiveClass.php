<?php

namespace Example\Lazy;


class ExpensiveClass implements ExpensiveInterface {

    public $serverDetails;

    function __construct($host, $username, $password, $port, $socket) {
        $this->serverDetails = $host.$username.$password.$port.$socket;

        //Only simulating the behaviour, we don't want to do the actual expensive
        //operation in a test.
        //$this->mySQLI =  new \mysqli ($host, $username, $password);
    }

    function executeQuery($params) {
        return 5;
    }
}


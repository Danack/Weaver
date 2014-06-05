<?php

namespace Example;


class MySQLConnection implements DBConnection {

    protected $queryString;

    private $forceUTF8Names = false;
    
    function __construct($host, $username, $password) {

        //Disabled, as we don't want to require a DB for a unit test
        if (false) {
            //This operation is slow, so should only be done when the connection is actually needed
            $this->mySQLI =  new \mysqli ($host, $username, $password);
        }
    }

    function anotherFunction($someParameter) {
        echo "Executing anotherFunction\n";
    }
    
    function executeQuery($params) {
        echo "executing query: ".$this->queryString."\n";
        usleep(300);
        return 5;
    }
    
    function noReturn() {
        echo "foo";
    }
    
    function __toString() {
        return "This shouldn't be extended";
    }
}


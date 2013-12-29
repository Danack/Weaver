<?php

namespace Example;


class TestClass implements TestInterface {

    protected $queryString;
    
    function __construct($queryString) {
        $this->queryString = $queryString;
    }

    function anotherFunction($someParameter) {
        echo "Executing anotherFunction\n";
    }
    
    function executeQuery($params) {
        echo "executing query: ".$this->queryString."\n";

        return 5;
    }
}


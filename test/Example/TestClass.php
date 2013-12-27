<?php

namespace Example;


class TestClass implements TestInterface {

    function __construct($statement, $createLine){
        $this->statement = $statement;
        $this->createLine = $createLine;
    }

    function foo($demBrackets) {
    }

    function executeQuery($queryString, $foo2) {
        echo "executing query!";
        
        return 5;
    }
}


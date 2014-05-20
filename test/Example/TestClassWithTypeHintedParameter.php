<?php

namespace Example;


class TestClassWithTypeHintedParameter implements TestInterface {

    protected $queryString;
    
    private $dependency;
    
    function __construct($queryString, TypeHintClass $dependency) {
        $this->queryString = $queryString;
        $this->dependency = $dependency;
    }

    function anotherFunction($someParameter) {
        echo "Executing anotherFunction\n";
    }
    
    function executeQuery($params) {
        echo "executing query: ".$this->queryString."\n";
        usleep(300);
        return 5;
    }

}


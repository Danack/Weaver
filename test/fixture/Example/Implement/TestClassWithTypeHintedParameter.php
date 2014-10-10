<?php

namespace Example\Implement;


class TestClassWithTypeHintedParameter implements ExpensiveInterface {

    protected $queryString;
    private $dependency;
    
    function __construct($queryString, TypeHintClass $dependency) {
        $this->queryString = $queryString;
        $this->dependency = $dependency;
    }

    function anotherFunction($someParameter) {
    }

    function executeQuery($params) {
        return 5;
    }
}


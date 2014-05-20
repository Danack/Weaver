<?php

namespace Example;



class TestClassWithDependencyMatchForProxy implements TestInterface {

    protected $queryString;
    
    function __construct(TypeHintClass $proxyDependency, $queryString) {
        $this->queryString = $queryString;
        $this->proxyDependency = $proxyDependency;
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


<?php


namespace Weaver\Test;


class TestClass {

    function __construct($statement, $createLine, TestInterface $interface){
        $this->interface = $interface;
        $this->statement = $statement;
        $this->createLine = $createLine;
    }

    function foo($demBrackets) {
    }

    function executeQuery($queryString, $foo2) {
        echo "executing query!";
    }
}

 
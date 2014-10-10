<?php

namespace Example;


class TestClass implements TestInterface {


    function anotherFunction($someParameter) {
    }
    
    function executeQuery($params) {
        return 6;
    }
    
    function noReturn() {
        echo "foo";
    }
    
    function __toString() {
        return "This shouldn't be extended";
    }
    
    function unextendedFunctionWithParam($foo) {
        return $foo;
    }
    
}


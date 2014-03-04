<?php


namespace Weaver;

//
//interface MethodMatcher {
//    function matches($methodName);
//}

class MethodMatcher {

    private $methods;

    function __construct(array $methods) {
        $this->methods = $methods;
    }

    function matches($methodName) {
        if (in_array($methodName, $this->methods)) {
            return true;
        }
        
        if (in_array('*', $this->methods)) {
            if (strpos($methodName, '__') !== 0) {
                return true;
            }
        }

        return false;
    }
}
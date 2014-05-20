<?php


namespace Weaver;


class MethodMatcher {

    private $methods;

    /**
     * @param array $methods
     */
    function __construct(array $methods) {
        $this->methods = $methods;
    }

    /**
     * @param $methodName
     * @return bool
     */
    function matches($methodName) {
        if (in_array($methodName, $this->methods)) {
            return true;
        }
        
        //TODO - this is a bit shit.
        if (in_array('*', $this->methods)) {
            if (strpos($methodName, '__') !== 0) {
                return true;
            }
        }

        return false;
    }
}
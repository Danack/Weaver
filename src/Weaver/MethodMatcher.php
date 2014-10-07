<?php



namespace Weaver;

interface MethodMatcher {

    /**
     * @param $methodName
     * @return bool
     */
    function matches($methodName);
}
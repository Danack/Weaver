<?php


namespace Weaver;


class MethodBinding {

    private $methodMatcher;
    private $method;

    /**
     * @param $method - The method in the decorating class.
     * @param MethodMatcher $methodMatcher - The methods in the source class to decorate.
     */
    function __construct($method, MethodMatcher $methodMatcher) {
        $this->method = $method;
        $this->methodMatcher = $methodMatcher;
    }

    /**
     * @param $methodName
     * @return bool
     */
    function matchesMethod($methodName) {
        return $this->methodMatcher->matches($methodName);
    }

    /**
     * @return mixed
     */
    public function getMethod() {
        return $this->method;
    }
}

 
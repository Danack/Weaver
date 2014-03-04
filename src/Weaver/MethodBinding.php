<?php


namespace Weaver;


class MethodBinding {

    private $methodMatcher;
    private $before;
    private $after;

    function __construct(MethodMatcher $methodMatcher, $before, $after, $hasResult = true) {
        $this->methodMatcher = $methodMatcher;
        $this->before = $before;
        $this->after = $after;
        $this->hasResult = $hasResult;
    }

    /**
     * @return boolean
     */
    public function getHasResult() {
        return $this->hasResult;
    }

    /**
     * @return mixed
     */
    public function getAfter() {
        return $this->after;
    }

    /**
     * @return mixed
     */
    public function getBefore() {
        return $this->before;
    }

    function matchesMethod($methodName) {
        return $this->methodMatcher->matches($methodName);
    }
}

 
<?php


namespace Weaver;


class MethodBinding {

    private $methodMatcher;
    private $before;
    private $after;

    /**
     * @param MethodMatcher $methodMatcher
     * @param $before
     * @param $after
     * @param bool $hasResult
     */
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

    /**
     * @param $methodName
     * @return bool
     */
    function matchesMethod($methodName) {
        return $this->methodMatcher->matches($methodName);
    }
}

 
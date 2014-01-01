<?php


namespace Weaver;


class MethodBinding {

    private $functionName;
    private $before;
    private $after;

    function __construct($functionName, $before, $after, $hasResult = true) {
        $this->functionName = $functionName;
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
     * @return mixed
     */
    public function getFunctionName() {
        return $this->functionName;
    }
}

 
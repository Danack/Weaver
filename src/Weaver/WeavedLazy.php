<?php


namespace Weaver;


class WeavedLazy implements TestInterface {

    private $lazyInstance;
    
    function __construct() {
    }
    
    function init() {
        if ($this->lazyInstance == null) {
            $this->lazyInstance = new FunctionalClass();
        }
    }
    
    function foo() {
        $this->init();
        return $this->lazyInstance->foo();
    }
}

 
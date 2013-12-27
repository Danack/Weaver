<?php


namespace Weaver\Weave;


class LazyProxy {

    private $lazyInstance;

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

 
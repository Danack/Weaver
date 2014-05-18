<?php


namespace Example;


class Component1 {

    private $component1Arg;
    
    function __construct($component1Arg) {
        $this->component1Arg = $component1Arg;
    }
    
    function renderElement() {
        return 'component1';
    }
}

 
<?php


namespace Example;


class Component2 {

    private $component2Arg;

    function __construct($component2Arg) {
        $this->component1Arg = $component2Arg;
    }


    function renderElement() {
        return 'component2';
    }
}

 
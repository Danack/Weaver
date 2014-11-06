<?php


namespace Example\Composite;


class Component2 implements CompositeInterface {

    function render() {
        return 'component2';
    }

    function unexposedMethod() {
        return 'component2';
    }
    
    function methodExposedExplicitly() {
        return 'foo';
    }
}


 
<?php


namespace Example\Composite;


class Component3 implements CompositeInterface {

    function render() {
        return 'component3';
    }

    function unexposedMethod() {
        return 'component3';
    }
    
    function methodExposedExplicitly() {
        return 'foo';
    }
}


 
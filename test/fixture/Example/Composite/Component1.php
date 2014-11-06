<?php


namespace Example\Composite;


class Component1 implements CompositeInterface {

    function render() {
        return 'component1';
    }

    function unexposedMethod() {
        return 'component1';
    }
    
    private function privateMethodNotUsedInInterface() {
    }
}

 
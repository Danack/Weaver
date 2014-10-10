<?php


namespace Example\Composite;


class Component2 implements CompositeInterface {

    function render() {
        return 'component2';
    }

    function methodNotInInterface() {
        return 'component2';
    }
}

 
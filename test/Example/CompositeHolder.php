<?php


namespace Example;



class CompositeHolder implements CompositeInterface {

    private $testValue;
    const output = 'CompositeHolder';
    
    function __construct($testValue) {
        //added for code coverage.
        $this->testValue = $testValue;
    }
    
    function renderElement() {
        return self::output;
    }
    
    function render() {
        return $this->renderElement();
    }
    
    function unused() {
        return self::output;   
    }
    
}

 
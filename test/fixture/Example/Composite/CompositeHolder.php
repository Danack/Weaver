<?php


namespace Example\Composite;



class CompositeHolder implements CompositeInterface {

    private $testValue;
    const output = 'CompositeHolder';
    
    function __construct($testValue) {
        //added for code coverage.
        $this->testValue = $testValue;
    }

    //This function is a placeholder, it is replaced in the Composite Weaved
    //version. It is useful to have a placeholder like this to be able to use
    //the method defined in the interface in other methods, without your IDE 
    //complaining about missing methods.
    function render() {
        return self::output;
    }

    function unexposedMethod() {
        return self::output;   
    }
}

 
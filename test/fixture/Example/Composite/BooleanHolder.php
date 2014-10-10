<?php


namespace Example\Composite;



class BooleanHolder implements BooleanInterface {

    //private $testValue;
    //const output = 'CompositeHolder';
    
    function __construct() {
        //added for code coverage.
        //$this->testValue = $testValue;
    }

    //This function is a placeholder, it is replaced in the Composite Weaved
    //version. It may be useful to have a placeholder like this to be able to use
    //the method defined in the interface in other methods, without your IDE 
    //complaining about missing methods.
    function isValid() {
        return true;
    }

//    function methodNotInInterface() {
//        return self::output;   
//    }
}

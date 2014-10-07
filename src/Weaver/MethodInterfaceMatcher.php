<?php


namespace Weaver;

use Danack\Code\Reflection\ClassReflection;


class MethodInterfaceMatcher implements MethodMatcher {

    /**
     * @var \ReflectionClass
     */
    private $interfaceReflection;
    
    function __construct($interfaceName) {
        //$this->interfaceReflection = new ClassReflection($interfaceName);
        $this->interfaceReflection = new \ReflectionClass($interfaceName);
    }
    
    
    /**
     * @param $methodName
     * @return bool
     */
    function matches($methodName) {
        return $this->interfaceReflection->hasMethod($methodName);
    }
}




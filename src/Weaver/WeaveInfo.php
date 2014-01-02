<?php


namespace Weaver;


class WeaveInfo {

    private $decoratorClass;
    
    private $methodBindingArray;
    
    protected $interface = null;

    function __construct($decoratorClass, array $methodBindingArray) {
        $this->decoratorClass = $decoratorClass;
        $this->methodBindingArray = $methodBindingArray;
    }

    /**
     * @return string
     */
    public function getDecoratorClass() {
        return $this->decoratorClass;
    }

    /**
     * @return MethodBinding[]
     */
    function getMethodBindingArray() {
        return $this->methodBindingArray;
    }



    function getInterface() {
        return $this->interface;
    }
   
}

 
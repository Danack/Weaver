<?php


namespace Weaver;


class CompositeWeaveInfo {

    private $composites;
    
    private $encapsulateMethods;
    
    function __construct(
        $decoratorClass, 
        array $composites,
        array $encapsulateMethods = []
    ) {
        $this->decoratorClass = $decoratorClass;
        $this->composites = $composites;
        $this->encapsulateMethods = $encapsulateMethods;

        \Intahwebz\Functions::load();
    }

    /**
     * @return array
     */
    public function getComposites() {
        return $this->composites;
    }
    
    function getEncapsulateMethods() {
        return $this->encapsulateMethods;
    }

    /**
     * @return string
     */
    public function getDecoratorClass() {
        return $this->decoratorClass;
    }
}

 
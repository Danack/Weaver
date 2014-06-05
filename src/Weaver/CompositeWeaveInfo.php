<?php


namespace Weaver;


class CompositeWeaveInfo {

    private $decoratorClass;

    private $encapsulateMethods;

    function __construct(
        $decoratorClass, 
        array $encapsulateMethods = []
    ) {
        $this->decoratorClass = $decoratorClass;
        $this->encapsulateMethods = $encapsulateMethods;

        \Intahwebz\Functions::load();
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

 
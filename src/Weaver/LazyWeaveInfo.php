<?php


namespace Weaver;


class LazyWeaveInfo extends WeaveInfo {

    private $initMethodName;
    private $lazyPropertyName;
    
    function __construct(
        $decoratorClass, 
        array $methodBindingArray, 
        $interface,
        $initMethodName,
        $lazyPropertyName
    ) {
        parent::__construct($decoratorClass, $methodBindingArray);

        $this->initMethodName = $initMethodName;
        $this->lazyPropertyName = $lazyPropertyName;
        $this->interface = $interface;
    }

    /**
     * @return string
     */
    public function getInitMethodName() {
        return $this->initMethodName;
    }

    /**
     * @return string
     */
    public function getLazyPropertyName() {
        return $this->lazyPropertyName;
    }
    
    
    
}

 
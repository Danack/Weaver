<?php


namespace Weaver;


class LazyWeaveInfo extends WeaveInfo {

    private $initMethodName;
    private $lazyPropertyName;
    
    function __construct(
        $decoratorClass, 
        array $methodBindingArray, 
        array $interfaces,
        $initMethodName,
        $lazyPropertyName
    ) {
        parent::__construct($decoratorClass, $methodBindingArray);

        $this->initMethodName = $initMethodName;
        $this->lazyPropertyName = $lazyPropertyName;
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

 
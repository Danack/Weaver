<?php


namespace Weaver;


class InstanceWeaveInfo extends WeaveInfo {

    private $initMethodName;
    private $lazyPropertyName;

    function __construct(
        $decoratorClass, 
        $interface,
        $initMethodName,
        $lazyPropertyName
    ) {
        parent::__construct($decoratorClass, null);
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

 
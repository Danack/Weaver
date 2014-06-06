<?php


namespace Weaver;


class ImplementsWeaveInfo {

    /**
     * The classname that the subject class(es) gets weaved with.
     * @var string
     */
    protected $decoratorClass;

    /**
     * Which methods of the weaved get intercepted.
     *
     * @var string[]
     */
    private $methodBindingArray = [];
    
    private $initMethodName;
    private $lazyPropertyName;
    protected $interface = null;

    /**
     * @param $sourceClass - The class that you want to wrap in a proxy
     * @param $decoratorClass - The proxy class, todo not really required?
     * @param $interface      - The interface that you want to expose todo support multiple interfaces
     * @param $initMethodName - What you want the init method to be called todo not really required?
     * @param $lazyPropertyName - What variable to store the proxied in todo not really required?
     * @param $lazyFactory - optional  
     * @param $instanceFactoryMethod
     */
    function __construct(
        $decoratorClass, 
        $interface, //TODO Allow multiple interfaces
        $initMethodName = null,
        $lazyPropertyName = null
    ) {
        $this->decoratorClass = $decoratorClass;
        $this->methodBindingArray = [];
        $this->interface = $interface;

        if ($initMethodName) {
            $this->initMethodName = $initMethodName;
        }
        else {
            $this->initMethodName = 'init';
        }

        if ($lazyPropertyName) {
            $this->lazyPropertyName = $lazyPropertyName;
        }
        else {
            $this->lazyPropertyName = 'lazyInstance';
        }

        if (interface_exists($interface) == false) {
            throw new WeaveException("Error in ImplementsWeaveInfo: ".$interface." does not exist");
        }
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

    /**
     * @return string
     */
    function getInitMethodName() {
        return $this->initMethodName;
    }

    /**
     * @return string
     */
    function getLazyPropertyName() {
        return $this->lazyPropertyName;
    }


    function getInterface() {
        return $this->interface;
    }
}

<?php


namespace Weaver;


class LazyWeaveInfo {

    use \Intahwebz\SafeAccess;
 
    /** @var string  */
    private $initMethodName;
    
    /** @var string  */
    private $lazyPropertyName;

    /** @var string  */
    protected $interface;

    /**
     * @param $decoratorClass - The decorating class
     * @param $interface - The interface that you want to expose todo support multiple interfaces
     * @param $initMethodName - What you want the init method to be called todo not really required?
     * @param $lazyPropertyName - What variable to store the proxied in todo not really required?
     * @throws WeaveException
     */
    function __construct(
        $interface, //TODO Allow multiple interfaces
        $initMethodName = null,
        $lazyPropertyName = null
    ) {
        $this->interface = $interface;

        if ($initMethodName) {
            if (is_string($initMethodName) == false) {
                throw new WeaveException(
                    "initMethodName should be a string, ".gettype($initMethodName)." given.",
                    WeaveException::METHOD_NAME_INVALID
                );
            }
            $this->initMethodName = $initMethodName;
        }
        else {
            $this->initMethodName = 'init';
        }

        if ($lazyPropertyName) {
            if (is_string($lazyPropertyName) == false) {
                throw new WeaveException(
                    "lazyPropertyName should be a string, ".gettype($lazyPropertyName)." given.",
                    WeaveException::PROPERTY_NAME_INVALID
                );
            }
            $this->lazyPropertyName = $lazyPropertyName;
        }
        else {
            $this->lazyPropertyName = 'lazyInstance';
        }

        if (interface_exists($interface) == false) {
            throw new WeaveException(
                "Error in ImplementsWeaveInfo: Interface ".$interface." does not exist",
                WeaveException::INTERFACE_NOT_VALID
            );
        }
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

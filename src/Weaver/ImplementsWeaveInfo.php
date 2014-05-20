<?php


namespace Weaver;


use Zend\Code\Generator\ParameterGenerator;

class ImplementsWeaveInfo {

    protected $sourceClass;

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
    private $lazyFactory;

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
        $sourceClass,
        $decoratorClass, 
        $interface,
        $initMethodName,
        $lazyPropertyName,
        $lazyFactory = null

    ) {
        $this->sourceClass = $sourceClass;
        $this->decoratorClass = $decoratorClass;
        $this->methodBindingArray = [];
        $this->initMethodName = $initMethodName;
        $this->lazyPropertyName = $lazyPropertyName;
        $this->interface = $interface;
        
        if ((is_array($lazyFactory) == true && count($lazyFactory) == 2)  || 
            is_string($lazyFactory) ||
            $lazyFactory === null) {
            //It's acceptable
        }
        else{
            throw new WeaveException("lazyFactory must either be a function, or an [interafaceName, method].");
        }

        $this->lazyFactory = $lazyFactory;
    }

    /**
     * @return string
     */
    public function getSourceClass() {
        return $this->sourceClass;
    }

    /**
     * @return string
     */
    public function getDecoratorClass() {
        return $this->decoratorClass;
    }

    /**
     * @TODO - this is not used in all sub-classes.
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

    /**
     * Returns the factory if specified by the config
     * @return string
     */
    function getInstanceFactorySignature() {

        if (!$this->lazyFactory) {
            return null;
        }

        if (is_array($this->lazyFactory)) {
            $factoryVar = lcfirst(getClassName($this->lazyFactory[0])); //byte-safe
            return '$this->'.$factoryVar.'->'.$this->lazyFactory[1];
        }

        return $this->lazyFactory;
    }

    /**
     * @return ParameterGenerator
     */
    function getFactoryParameterGenerator() {
        if (!$this->lazyFactory) {
            return null;
        }
        
        if (is_string($this->lazyFactory)) {
            //factories which are functions cannot be passed in yet
            return null;
        }

        $name = lcfirst(getClassName($this->lazyFactory[0]));

        return new ParameterGenerator($name, $this->lazyFactory[0]);
    }

    function getInterface() {
        return $this->interface;
    }

    function getLazyFactory() {
        if ($this->lazyFactory) {
            if (is_array($this->lazyFactory)) {
                return $this->lazyFactory[0];
            }
        }

        return null;
    }
}

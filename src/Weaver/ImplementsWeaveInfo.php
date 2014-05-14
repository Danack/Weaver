<?php


namespace Weaver;


use Zend\Code\Generator\ParameterGenerator;

class ImplementsWeaveInfo extends WeaveInfo {

    private $initMethodName;
    private $lazyPropertyName;
    private $instanceFactoryName;
    protected $interface = null;

    function __construct(
        $sourceClass,
        $decoratorClass, 
        $interface,
        $initMethodName,
        $lazyPropertyName,
        $instanceFactoryName = null,
        $instanceFactoryMethod = null
    ) {
        parent::__construct($sourceClass, $decoratorClass, null);
        $this->initMethodName = $initMethodName;
        $this->lazyPropertyName = $lazyPropertyName;
        $this->interface = $interface;

        if ($instanceFactoryName xor $instanceFactoryMethod) {
            throw new \Exception("Either both \$instanceFactoryName and \$instanceFactoryMethod should be set, or neither.");
        }

        $this->instanceFactoryName = $instanceFactoryName;
        $this->instanceFactoryMethod = $instanceFactoryMethod;
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
        return '$this->'.lcfirst($this->instanceFactoryName).'->'.$this->instanceFactoryMethod; //byte-safe
    }

    /**
     * @return null
     */
    function getFactoryParam() {
        if (!$this->instanceFactoryName) {
            return null;
        }
        $params = array();
        $params['name'] = lcfirst($this->instanceFactoryName); // Class Name
        $params['type'] = $this->instanceFactoryName;

        return ParameterGenerator::fromArray($params);
    }


    function getInterface() {
        return $this->interface;
    }
}

 
<?php


namespace Weaver;


class ImplementWeaveInfo {

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


    /** @var string  */
    private $instancePropertyName;

    /** @var string  */
    private $interface;
    
    

    /**
     * @param string $decoratorClass
     * @param MethodBinding[] $methodBinding
     * @internal param string $sourceClass
     */
    function __construct(
        $decoratorClass, 
        $interface, //TODO Allow multiple interfaces
        array $methodBinding,
        $instancePropertyName = null
    ) {
        $this->decoratorClass = $decoratorClass;
        $this->methodBindingArray = $methodBinding;
        $this->instancePropertyName = $instancePropertyName;
        
        if (is_string($interface) == null) {
            throw new WeaveException("Interface must be set as a string for ImplementWeaveInfo.", WeaveException::INTERFACE_NOT_SET);
        }

        $this->interface = $interface;

        if (!$this->instancePropertyName) {
            $this->instancePropertyName = 'weavedInstance';
        }
    }

    /**
     * @return string
     */
    public function getDecoratorClass() {
        return $this->decoratorClass;
    }

    /**
     * @return string
     */
    public function getInstancePropertyName() {
        return $this->instancePropertyName;
    }

    /**
     * @return string
     */
    public function getInterface() {
        return $this->interface;
    }

    /**
     * @return MethodBinding[]
     */
    function getMethodBindingArray() {
        return $this->methodBindingArray;
    }
}

 
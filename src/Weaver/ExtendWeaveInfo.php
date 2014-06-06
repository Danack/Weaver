<?php


namespace Weaver;


class ExtendWeaveInfo {

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

    /**
     * @param string $sourceClass
     * @param string $decoratorClass
     * @param MethodBinding[] $methodBinding
     */
    function __construct($decoratorClass, array $methodBinding) {
//        $this->sourceClass = $sourceClass;
        $this->decoratorClass = $decoratorClass;
        $this->methodBindingArray = $methodBinding;
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
}

 
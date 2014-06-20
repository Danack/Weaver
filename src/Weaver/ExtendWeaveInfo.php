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
     * @param string $decoratorClass
     * @param MethodBinding[] $methodBinding
     * @internal param string $sourceClass
     */
    function __construct($decoratorClass, array $methodBinding) {
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
     * @return MethodBinding[]
     */
    function getMethodBindingArray() {
        return $this->methodBindingArray;
    }
}

 
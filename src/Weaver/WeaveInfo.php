<?php


namespace Weaver;


abstract class WeaveInfo {

    /**
     * The classname that the subject class(es) gets weaved with.
     * @TODO why is this here?
     * @var string
     */
    protected $decoratorClass;

    /**
     * Which methods of the weaved get intercepted.
     * 
     * @var string[]
     */
    private $methodBindingArray;
    
    protected $interface = null;

    function __construct($decoratorClass, MethodBinding $methodBinding = null) {
        $this->decoratorClass = $decoratorClass;
        $this->methodBindingArray = array();

        $args = func_get_args();

        for ($i=1 ; $i<func_num_args() ; $i++) {
            $methodBinding = $args[$i];
            if ($methodBinding != null) {
                $this->methodBindingArray[] = $methodBinding;
            }
        }
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

    function getInterface() {
        return $this->interface;
    }
}

 
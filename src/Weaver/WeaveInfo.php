<?php


namespace Weaver;


abstract class WeaveInfo {

    
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
    private $methodBindingArray;
    


    function __construct($sourceClass, $decoratorClass, MethodBinding $methodBinding = null) {
        $this->sourceClass = $sourceClass;
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


}

 
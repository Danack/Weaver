<?php


namespace Weaver;


class CompositeWeaveInfo extends WeaveInfo {

    private $composites;
    
    private $encapsulateMethods;
    
    function __construct(
        $decoratorClass, 
        array $composites,
        array $encapsulateMethods = []
    ) {

        parent::__construct($decoratorClass, null);
        $this->decoratorClass = $decoratorClass;
        $this->composites = $composites;
        $this->encapsulateMethods = $encapsulateMethods;

        \Intahwebz\Functions::load();
    }

//    function getOutputClassname() {
//        return 'ImagickDemo\Control\ControlCompositeXRadiusXSigmaXImage';
//    }
    
    
    /**
     * @return array
     */
    public function getComposites() {
        return $this->composites;
    }
    
    function getEncapsulateMethods() {
        return $this->encapsulateMethods;
    }

}

 
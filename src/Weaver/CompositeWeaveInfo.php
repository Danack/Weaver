<?php


namespace Weaver;


class CompositeWeaveInfo {

    private $decoratorClass;

    private $encapsulateMethods;
    
    //const   RETURN_BLOB = 'blob';
    const   RETURN_ARRAY = 'array';
    const   RETURN_STRING = 'string';

    /**
     * @param $decoratorClass
     * @param array $encapsulateMethods
     * @TODO Allow the interface to be specified.
     */
    function __construct($decoratorClass, array $encapsulateMethods = []) {
        $this->decoratorClass = $decoratorClass;
        $this->encapsulateMethods = $encapsulateMethods;

        \Intahwebz\Functions::load();
    }

    /**
     * @return mixed
     */
    function getEncapsulateMethods() {
        return $this->encapsulateMethods;
    }

    /**
     * @return string
     */
    public function getDecoratorClass() {
        return $this->decoratorClass;
    }
}

 
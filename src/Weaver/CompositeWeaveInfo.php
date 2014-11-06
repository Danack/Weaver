<?php


namespace Weaver;


class CompositeWeaveInfo {

    private $decoratorClass;

    private $encapsulateMethods;
    
    private $exposeMethods;
    
    /** Combine all return values in array e.g.
     *
     * public function getParams()
     * { 
     *
     *     $result = [];
     *     $result = array_merge($result, $this->element1->getParams());
     *     $result = array_merge($result, $this->element2->getParams());
     *
     *     return $result;
     * }
     */
    const   RETURN_ARRAY = 'array';
    
    /** return type string - concatenates all the return values 
     *
     * public function renderItem()
     * { 
     *     $result = '';
     *     $result .= $this->element1->renderItem();
     *     $result .= $this->element2->renderItem();;
     *
     *     return $result;
     * }
     * 
     * 
     */
    const   RETURN_STRING = 'string';
    
    
    /** Combine true if all composite elements return true
     *
     * public function isValid()
     * { 
     *     $result = true;
     *     $result &= $this->element1->isValid();
     *     $result &= $this->element2->isValid();;
     *
     *     return $result;
     * }
     *
     */
    const   RETURN_BOOLEAN = 'boolean';

    /**
     * @param $decoratorClassOrInterface
     * @param array $methodCompositionTypes Which composition method to use for each method
     * @TODO Allow the interface to be specified.
     */
    function __construct(
        $decoratorClassOrInterface,
        array $methodCompositionTypes = [],
        array $exposeMethods = []    
    ) {
        $this->decoratorClass = $decoratorClassOrInterface;
        $this->encapsulateMethods = $methodCompositionTypes;
        $this->exposeMethods = $exposeMethods;

        \Intahwebz\Functions::load();
    }

    /**
     * @return mixed
     */
    function getEncapsulateMethods() {
        return $this->encapsulateMethods;
    }

    /**
     * @return array
     */
    public function getExposeMethods() {
        return $this->exposeMethods;
    }

    
    
    /**
     * @return string
     * @TODO - this is a stupid name
     */
    public function getDecoratorClass() {
        return $this->decoratorClass;
    }
}

 
<?php


namespace Example\Implement;


class ClassWithConstructor implements \Example\TestInterface{

    public $foo;
    
    function __construct($foo) {
        $this->foo = $foo;
    }

    /**
     * @return mixed
     */
    public function getFoo() {
        return $this->foo;
    }

    function anotherFunction($someParameter) {
    }

    function executeQuery($params) {
        return 6;
    }
}

 
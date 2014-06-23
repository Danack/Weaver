<?php


namespace Example;


class StandardTestClassFactory implements TestClassFactory {

//    private $closure;
//
//    function __construct(callable $closure) {
//        $this->closure = $closure;
//    }

    function create($statement) {
        //$function = $this->closure;

        return new TestClass($statement);
    }
}

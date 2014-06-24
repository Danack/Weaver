<?php


namespace Example\Implement;

class ClassWithConstructorClosureFactory {

    private $closure;

    function __construct(callable $closure) {
        $this->closure = $closure;
    }

    /**
     * @param $foo
     * @return ClassWithConstructor
     */
    function create($foo) {

        $function = $this->closure;
        $expensiveClass = $function($foo);

        return $expensiveClass;
    }
}

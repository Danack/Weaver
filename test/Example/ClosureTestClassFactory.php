<?php


namespace Example;


class ClosureTestClassFactory implements TestClassFactory {

    private $closure;

    function __construct(callable $closure) {
        $this->closure = $closure;
    }

    function create($queryString) {
        $function = $this->closure;

        return $function ($queryString);
    }
}


 
<?php


namespace Example;


class ClosureTestClassFactory {

    private $closure;

    function __construct(callable $closure) {
        $this->closure = $closure;
    }

    function create($statement, $createLine) {
        $function = $this->closure;

        return $function ($statement, $createLine);
    }
}



function createClosureStatementWrapperFactory(\Intahwebz\Timer $timer) {

    $closure = function ($statement, $createLine)
        use ($timer)
    {

        $statementWrapper = new \Example\TimerProxyXTestClass(
            $statement, $createLine, $timer
        );

        return $statementWrapper;
    };

    return new \Example\ClosureTestClassFactory($closure);
}
<?php


namespace Example\Lazy;

class ExpensiveClassClosureFactory {

    private $closure;

    function __construct(callable $closure) {
        $this->closure = $closure;
    }

    /**
     * @param $host
     * @param $username
     * @param $password
     * @param $port
     * @param $socket
     * @return ExpensiveClass
     */
    function create($host, $username, $password, $port, $socket) {

        $function = $this->closure;
        $expensiveClass = $function($host, $username, $password, $port, $socket);

        return $expensiveClass;
    }
}

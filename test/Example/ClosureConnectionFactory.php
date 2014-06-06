<?php


namespace Example;

use Psr\Log\LoggerInterface;

class ClosureConnectionFactory {

    private $closure;

    function __construct(callable $closure) {
        $this->closure = $closure;
    }

    /**
     * @param LoggerInterface $logger
     * @param StatementFactory $statementWrapperFactory
     * @param $host
     * @param $username
     * @param $password
     * @param $port
     * @param $socket
     * @return DBConnection
     */
    function create(LoggerInterface $logger,
                    StatementFactory $statementWrapperFactory,
                    $host, $username, $password, $port, $socket) {
        
        $function = $this->closure;
        $connection = $function($logger,
                                      $statementWrapperFactory,
                    $host, $username, $password, $port, $socket);

        return $connection;
    }
}

<?php


namespace Example;

use Psr\Log\LoggerInterface;


interface StatementFactory {

    /**
     * @param \mysqli_stmt $statement
     * @param $queryString
     * @param \Psr\Log\LoggerInterface $logger
     * @return object
     */
    function create(\mysqli_stmt $statement, $queryString, LoggerInterface $logger);
} 
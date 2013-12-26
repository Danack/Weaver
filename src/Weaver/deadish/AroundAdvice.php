<?php

interface DBConnection {}
interface LoggerInterface {}

class ConnectionWrapper {}

class SomeClass {
    function parentFunction($params) {
        
    }
}

class TimerClass {
    function before() {
    }

    function after() {
    }
}



class AroundAdvice extends SomeClass {
    
    private $timerClass;

    function __construct(TimerClass $timerClass, $otherParams) {
        $this->timerClass = $timerClass;
    }
    
    function parentFunction($params) {
        $this->timerClass->before();
        parent::parentFunction($params);
        $this->timerClass->after();
    }
}




class ProxiedConnectionWrapper implements DBConnection {

    /**
     * @var DBConnection
     */
    private $instance = null;

    private $host;
    private $username;
    private $password;
    private $port;
    private $socket;

    function __construct(LoggerInterface $logger, $host, $username, $password, $port, $socket) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
        $this->socket = $socket;
        $this->logger = $logger;
    }

    function checkInstance() {
        if ($this->instance == null) {
            $this->instance = new ConnectionWrapper(
                $this->logger,
                $this->host,
                $this->username,
                $this->password,
                $this->port,
                $this->socket
            );
        }
    }

    function activateTransaction() {
        $this->checkInstance();
        return $this->instance->activateTransaction();
    }
    
}
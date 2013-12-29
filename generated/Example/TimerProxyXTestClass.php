<?php

//Auto-generated by Weaver - https://github.com/Danack/Weaver
//
//Do not be surprised if any changes to this file are over-written.

namespace Example;

class TimerProxyXTestClass extends \Example\TestClass
{

    private $timer = null;

    public function executeQuery($params)
    {
        $this->timer->startTimer($this->queryString);
        $result = parent::executeQuery($params);
        $this->timer->stopTimer();

        return $result;
    }

    public function reportTimings()
    {
        $this->timer->dumpTime();
    }

    public function __construct($queryString, \Intahwebz\Timer $timer)
    {
        parent::__construct($queryString);
                $this->timer = $timer;
    }


}
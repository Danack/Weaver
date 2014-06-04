<?php


namespace Weaver\Weave;

use Intahwebz\Timer;


class TimerProtoProxy {

    private $timer;

    private $startTime = null;
    private $timings = [];
    
    function __construct(Timer $timer) {
        $this->timer = $timer;
    }

    function getTimings() {
        return $this->timings;
    }
    
    function reportTimings() {
        foreach ($this->timings as $timing) {
            printf(
                "Time: %s, Method: %s, Data: %s",
                $timing[0],
                $timing[1],
                $timing[2]
            );
        }
    }

    function __prototype() {
        //You don't have to list this, but it keeps your ide happy
    }

    function __extend($param0) {
        $this->startTimer();
        $result = $this->__prototype();
        $this->stopTimer(__METHOD__, $param0);
        return $result;
    }

    private function startTimer() {
        $this->startTime = $this->getTime();
    }

    private function getTime() {
        list($usec,$sec) = explode(' ', microtime());
        return ((float)$usec + (float)$sec);
    }

    private function stopTimer($methodName, $description) {
        $endTime = $this->getTime();
        $timeTaken = $endTime - $this->startTime;
        $this->timings[] = [$timeTaken, $methodName, $description];
    }
}

 
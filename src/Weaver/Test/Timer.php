<?php


namespace Weaver\Test;


class Timer {

    private $timeRecords = array();

    private $startTime = null;
    private $lineNumber = null;
    private $description = null;

    function startTimer($lineNumber, $description) {
        if ($this->startTime != null) {
            $this->stopTimer();
        }

        $this->startTime = microtime(true);
        $this->lineNumber = $lineNumber;
        $this->description = $description;
    }

    function stopTimer() {
        $time = microtime(true) - $this->startTime;
        $this->timeRecords[] = array($time, $this->lineNumber, $this->description);

        $this->startTime = null;
        $this->lineNumber = null;
        $this->description = null;
    }

    function dumpTime() {
        var_dump($this->timeRecords);
    }
}

 
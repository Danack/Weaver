<?php


namespace Weaver\Weave;

use Intahwebz\Timer;


class TimerProxy {

    private $timer;

    function __construct(Timer $timer) {
        $this->timer = $timer;
    }

    function reportTimings() {
        $this->timer->dumpTime();
    }
}

 
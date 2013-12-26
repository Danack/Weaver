<?php

namespace Weaver;

class TimerClass implements TestInterface {

    private $obj;
    
    function __construct(TestInterface $obj) {
        $this->obj = $obj;
    }

    function foo() {
        $time_start = microtime(true);
        $this->obj->foo();
        $time = microtime(true) - $time_start;
        echo "time taken = ".number_format($time, 4)."<br/>";
    }
}


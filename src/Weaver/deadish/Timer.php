<?php


namespace Weaver;


class Timer {

    function start() {
        $this->time_start = microtime(true);
    }

    function end(){
        $time = microtime(true) - $this->time_start;
        echo "time taken = ".number_format($time, 4)."<br/>";
    }
    
}

 
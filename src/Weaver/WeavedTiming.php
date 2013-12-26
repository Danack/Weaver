<?php


namespace Weaver;


class Weaved implements TestInterface {

    private $timer;
    private $obj;

    function __construct(TestInterface $obj, Timer $timer) {
        $this->obj = $obj;
        $this->timer = $timer;
    }

    function foo() {
        $this->timer->start();
        $this->obj->foo();
        $this->timer->end();
    }
}

 
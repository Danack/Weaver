<?php

use Weaver\LazyWeaveInfo;
use Weaver\Weaver;

interface FooInterface {
    function foo();
}


class Expensive implements FooInterface {

    function __construct($dsn, $user, $password) {
        $this->pdo = new PDO($dsn, $user, $password);
    }
    
    function foo() {
        //$this->pdo 
    }
}

$lazyWeaveInfo = new LazyWeaveInfo('FooInterface');

$result = Weaver::weave('ExpensiveClass', $lazyWeaveInfo);
$classname = $result->writeFile($this->outputDir, 'Example\Coverage\LazyProxyXTestClass');

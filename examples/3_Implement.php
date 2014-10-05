<?php

require '../vendor/autoload.php';

use Weaver\MethodBinding;
use Weaver\MethodMatcher;
use Weaver\ImplementWeaveInfo;
use Weaver\Weaver;


interface FooInterface {
    function foo();
}


class Bar implements FooInterface {

    function foo() {
        echo "This is foo!";
    }
}



class ImplementDecorator {

    function __prototype() {
        echo "This is a __prototype!";
    }

    function __extend() {
        echo "Before call.".PHP_EOL;
        $this->__prototype();
        echo "After prototype.".PHP_EOL;
    }
}


$methodBinding = new MethodBinding(
    '__extend',
    new MethodMatcher(['foo'])
);

$weaveInfo = new ImplementWeaveInfo(
    'ImplementDecorator',
    'FooInterface',
    [$methodBinding]
);


$weaveResult = Weaver::weave('Bar', $weaveInfo);
$classname = $weaveResult->writeFile('./output', 'DecoratedBar');
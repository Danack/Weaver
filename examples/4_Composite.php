<?php

require '../vendor/autoload.php';

use Weaver\CompositeWeaveInfo;
use Weaver\Weaver;


class Element1 {
    function foo() {
        return "Element 1";
    }
}

class Element2 {
    function foo() {
        return "Element 2";
    }
}


class Composite {
    function foo() {
        return "This gets replaced";
    }
}


$components = [
    'Element1',
    'Element2'
];

$compositeWeaveInfo = new \Weaver\CompositeWeaveInfo(
    'Composite',
    ['foo' => CompositeWeaveInfo::RETURN_STRING,]
);

$outputClassname = 'Composite';

$result = Weaver::weave($components, $compositeWeaveInfo);
$result->writeFile('./output', 'Composite');
<?php

require '../vendor/autoload.php';

use Weaver\MethodBinding;
use Weaver\MethodNameMatcher;
use Weaver\ExtendWeaveInfo;
use Weaver\Weaver;


class Greeter {

    function format($string) {
        return $string.PHP_EOL;
    }

    function sayHello() {
        return $this->format("Hello");
    }
    
    function sayGoodbye() {
        return $this->format("Bye-bye");
    }
}


class HtmlFormatterProxy {
    function __extend() {
        $string = $this->__prototype();
        $newString = "<span>".trim($string)."</span>";

        return $newString;
    }

    /**
     * This method is not required to be defined, however it makes
     * your IDE be much happier.
     */
    function __prototype() {
        return 'foo';
    }
}

$cacheMethodBinding = new MethodBinding(
    '__extend',
    new MethodNameMatcher(['format'])
);


$htmlWeaveInfo = new ExtendWeaveInfo(
    'HtmlFormatterProxy',
    [$cacheMethodBinding]
);

$result = Weaver::weave('Greeter', $htmlWeaveInfo);
$result->writeFile('./output', 'HtmlGreeter');


require_once('./output/HtmlGreeter.php');


$greeter = new HtmlGreeter();
echo $greeter->sayHello();

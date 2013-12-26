<?php

namespace Weaver;

require_once "../vendor/autoload.php";

$foo = new FunctionalClass();

$testObj = new TimerClass($foo);

$testObj->foo();


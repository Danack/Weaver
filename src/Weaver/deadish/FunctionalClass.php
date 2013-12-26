<?php

namespace Weaver;

class FunctionalClass implements TestInterface {

    function foo() {
        echo "foo";
        usleep(100);
    }

}

?> 
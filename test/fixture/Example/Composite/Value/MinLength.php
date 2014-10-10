<?php

namespace Example\Composite\Value;

class MinLength implements Validator {

    function __construct($minLength) {
        $this->minLength = $minLength;
    }

    function isValid($value) {
        if (strlen($value) >= $this->minLength) {
            return true;
        }
        
        return false;
    }
}

 
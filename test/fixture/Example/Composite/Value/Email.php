<?php

namespace Example\Composite\Value;

class Email implements Validator {
    
    function isValid($value) {
        if (strpos($value, '@') === false) {
            return false;
        }
        
        return true;
    }
}


 
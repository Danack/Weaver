<?php
    function createLazyProxyXTestClassFactory() {
        $closure = function ($queryString)  {
            $object = new \Example\TestClass(
                $queryString
            );
    
            return $object;
        };
    
        return new Example\StandardTestClassFactory($closure);  
    }
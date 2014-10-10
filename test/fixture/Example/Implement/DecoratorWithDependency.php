<?php


namespace Example\Implement;


class DecoratorWithDependency {

    private $proxyDependency;
    
    function __construct(TypeHintClass $proxyDependency) {
        $this->proxyDependency = $proxyDependency;
    }
}

 
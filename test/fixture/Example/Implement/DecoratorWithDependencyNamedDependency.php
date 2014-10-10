<?php


namespace Example\Implement;


class DecoratorWithDependencyNamedDependency {

    private $proxyDependency;
    
    function __construct(TypeHintClass $dependency, $dependencyNotInProxiedClass) {
        $this->proxyDependency = $dependency;
        $this->dependencyNotInProxiedClass = $dependencyNotInProxiedClass;
    }
}

 
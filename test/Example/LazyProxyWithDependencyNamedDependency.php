<?php


namespace Example;


class LazyProxyWithDependencyNamedDependency {

    private $proxyDependency;
    
    function __construct(TypeHintClass $dependency, $dependencyNotInProxiedClass) {
        $this->proxyDependency = $dependency;
        $this->dependencyNotInProxiedClass = $dependencyNotInProxiedClass;
    }
}

 
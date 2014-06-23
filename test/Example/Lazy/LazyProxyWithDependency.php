<?php


namespace Example\Lazy;


class LazyProxyWithDependency {

    private $proxyDependency;
    
    function __construct(TypeHintClass $proxyDependency) {
        $this->proxyDependency = $proxyDependency;
    }
}

 
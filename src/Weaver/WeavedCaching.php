<?php


namespace Weaver;


class WeavedCaching {

    const CACHE_KEY = '12345';
    
    private $timer;
    private $obj;

    function __construct(TestInterface $obj, Cache $cache) {
        $this->obj = $obj;
        $this->cache = $cache;
    }

    function foo() {

        $cachedValue = $this->cache->get(CACHE_KEY);
        
        if ($cachedValue) {
            return $cachedValue;
        }
        
        $value = $this->obj->foo();
        $this->cache->put(CACHE_KEY, $value);

        return $value; 
    }
}

 
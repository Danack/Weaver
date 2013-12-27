<?php


namespace Weaver\Weave;

use Intahwebz\ObjectCache;


class CacheProxy {

//    const CACHE_KEY = '12345';

    /**
     * @var \Intahwebz\ObjectCache
     */
    private $cache;

    function __construct(ObjectCache $cache) {
        $this->cache = $cache;
    }
    
    function getCacheKey() {
        $args = func_get_args();

        $cacheKey = '';
        
        foreach($args as $arg) {
            $cacheKey = hash ("sha256", $cacheKey.$arg);
        }

        return $cacheKey;
    }
    
}

 
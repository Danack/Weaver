<?php


namespace Weaver\Weave;

use Intahwebz\ObjectCache;


class CacheProtoProxy {

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
            if (is_array($arg)) {
                foreach ($arg as $argElement) {
                    $cacheKey = hash("sha256", $cacheKey.$argElement);
                }
            }
            else {
                $cacheKey = hash("sha256", $cacheKey.$arg);
            }
        }

        return $cacheKey;
    }

    function __prototype() {
        return 'value';
    }

    function __extend() {
        $cacheKey = call_user_func_array([$this, 'getCacheKey'], func_get_args());
        $cachedValue = $this->cache->get($cacheKey);
        
        if ($cachedValue) {
           return $cachedValue;
        }

        $result = $this->__prototype();
        $this->cache->put($cacheKey, $result, 360);
        return $result;
    }
}
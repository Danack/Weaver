<?php


namespace Weaver\Weave;

use Intahwebz\ObjectCache;


class CacheProxy {

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

//    //TODO - would it be better to define the binding like this?
//    function __prototype() {
//        $cacheKey = $this->getCacheKey($queryString);
//        $cachedValue = $this->cache->get($cacheKey);
//
//        if ($cachedValue) {
//            return $cachedValue;
//        }
//        $result = parent::__prototype();
//        $this->cache->put($cacheKey, $result);
//        return $result;
//    }

}
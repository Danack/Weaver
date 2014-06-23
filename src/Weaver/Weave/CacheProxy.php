<?php


namespace Weaver\Weave;

use Intahwebz\ObjectCache;


//This sould be called CacheDecorator
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
}
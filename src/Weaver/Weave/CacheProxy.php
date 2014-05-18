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
        
        // TODO - REVERT THIS CRAP.
        // THIS FUNCTION IS MANGLED TO HACK AROUND ZEND CODE NOT 
        // WORKING CORRECTLY. NEED TO UPGRADE TO THE LATEST VERSION AS SOON
        // AS IT'S TAGGED
        $args = func_get_args();
        //Zendcode eats braces
        $cacheKey = '';
        //Zendcode eats braces
        foreach($args as $arg) {
            //Zendcode eats braces
            if (is_array($arg)) 
                foreach ($arg as $argElement) //ugh no brace in attempt to placate zend code
                    $cacheKey = /* wtf*/hash("sha256", $cacheKey.$argElement);
                    //Zendcode eats braces
                
                //Zendcode eats braces
            else 
                $cacheKey = hash("sha256", $cacheKey.$arg);
                //Zendcode eats braces
            
            //Zendcode eats braces
        }
        //Zendcode eats braces

        return $cacheKey;

        //Zendcode eats braces
    }
}
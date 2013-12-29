<?php


require_once('../vendor/autoload.php');

use Weaver\WeaveInfo;
use Weaver\MethodBinding;


$timerWeaveInfo = new WeaveInfo(
    'Weaver\Weave\TimerProxy',
    array(
            new MethodBinding(
                'executeQuery',
                '$this->timer->startTimer($this->queryString);',
                '$this->timer->stopTimer();'
            )
    )
);



$cacheWeaveInfo = new WeaveInfo(
    'Weaver\Weave\CacheProxy',
    array(
        new MethodBinding('executeQuery',
            '
           $cacheKey = $this->getCacheKey($this->queryString);
           $cachedValue = $this->cache->get($cacheKey);
           
           if ($cachedValue) {
               echo "Result is in cache.\n";
               return $cachedValue;
           }
           ',
            'echo "Result was not in cache\n";
                $this->cache->put($cacheKey, $result);'
        ),
    )
);


$weaver = new \Weaver\Weaver();

$weaver->extendWeaveClass(
    'Example\TestClass', 
    array(
        $timerWeaveInfo,
        $cacheWeaveInfo,
    ),
    '../generated/'
);


$weaver->writeClosureFactories(
    '../generated/Example/',
    'Example',
    'ClosureFactory',
    $weaver->getClosureFactories()
);

<?php


require_once('../vendor/autoload.php');


$timerWeaving = array(
    'executeQuery' => array(
        'before' => '$this->timer->startTimer($this->queryString);', 
        'after' => '$this->timer->stopTimer();'
    ),
);


$cacheWeaving = array(
    'executeQuery' => array(
        'before' => '
        $cacheKey = $this->getCacheKey($this->queryString);
        $cachedValue = $this->cache->get($cacheKey);
        
        if ($cachedValue) {
            echo "Result is in cache.\n";
            return $cachedValue;
        }
        ',

        'after' => 'echo "Result was not in cache\n";
        $this->cache->put($cacheKey, $result);'
    ),
);



$weaver = new \Weaver\Weaver();

$weaver->extendWeaveClass(
    'Example\TestClass', 
    array(
        array('Weaver\Weave\TimerProxy', $timerWeaving),
        array('Weaver\Weave\CacheProxy', $cacheWeaving),
    ),
    '../generated/'
);







$weaver->writeClosureFactories(
    '../generated/Example/',
    'Example',
    'ClosureFactory',
    $weaver->getClosureFactories()
);

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






if (false) {



$weaver = new \Weaver\Weaver();
$lazyWeaving = array(
    'init' => 'init',
    'lazyProperty' => 'lazyInstance',
    'interfaces' => array('TestInterface'),
);


$weaver = new \Weaver\Weaver();
$weaver->instanceWeaveClass(
    'Example\TestClass',
    'Weaver\Weave\LazyProxy',
    $lazyWeaving,
    '../generated/'
);


$lazyWeaving = array(
    'init' => 'init',
    'lazyProperty' => 'lazyInstance',
    'interfaces' => array('\Example\DBConnection'),
);

$weaver = new \Weaver\Weaver();
$weaver->instanceWeaveClass(
    'Example\ConnectionWrapper',
    'Weaver\Weave\LazyProxy',
    $lazyWeaving,
    '../generated/'
);

}


$weaver->writeClosureFactories(
    '../generated/Example/',
    'Example',
    'ClosureFactory',
    $weaver->getClosureFactories()
);

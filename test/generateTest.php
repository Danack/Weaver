<?php


require_once('../vendor/autoload.php');

use Weaver\WeaveInfo;
use Weaver\MethodBinding;
use Weaver\LazyWeaveInfo;


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

$weaver->weaveClass(
    'Example\TestClass', 
    array(
        $timerWeaveInfo,
        $cacheWeaveInfo,
    ),
    '../generated/'
);




$lazyWeaveInfo = new LazyWeaveInfo(
    'Weaver\Weave\LazyProxy',
    array(),
    array('TestInterface'),
    'init',
    'lazyInstance'
);


$weaver->weaveClass(
    'Example\TestClass',
    array(
        $lazyWeaveInfo,
    ),
    '../generated/'
);


//
//$lazyWeaving = array(
//    'init' => 'init',
//    'lazyProperty' => 'lazyInstance',
//    'interfaces' => array('TestInterface'),
//);
//
//
//$weaver->instanceWeaveClass(
//    'Example\TestClass',
//    'Weaver\Weave\LazyProxy',
//    $lazyWeaving,
//    '../generated/'
//);




//This always needs to be last
$weaver->writeClosureFactories(
    '../generated/',
    'Example',
    'ClosureFactory',
    $weaver->getClosureFactories()
);


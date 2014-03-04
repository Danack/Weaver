<?php


namespace Weaver;

use Weaver\ExtendWeaveInfo;
use Weaver\MethodBinding;
use Weaver\InstanceWeaveInfo;



class BasicTest extends \PHPUnit_Framework_TestCase {

    
    function testExtendWeave() {

        $methodBinding = new MethodBinding(
            new MethodMatcher(['executeQuery', 'noReturn']),
            '$this->timer->startTimer($this->queryString);',
            '$this->timer->stopTimer();'
        );

        $timerWeaveInfo = new ExtendWeaveInfo(
            'Weaver\Weave\TimerProxy',
            $methodBinding
        );

        $cacheWeaveInfo = new ExtendWeaveInfo(
            'Weaver\Weave\CacheProxy',

            new MethodBinding(
                new MethodMatcher(['executeQuery']),
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
            )
        );


        $weaver = new Weaver();

        $weaver->weaveClass(
            'Example\TestClass',
            array(
                $timerWeaveInfo,
                $cacheWeaveInfo,
            ),
            '../generated/',
            'ClosureTestClassFactory'
        );

        $this->writeFactories($weaver);
    }


    /**
     * 
     */
    function testInstanceWeave() {
        $weaver = new Weaver();

        $lazyWeaveInfo = new InstanceWeaveInfo(
            'Weaver\Weave\LazyProxy',
            'TestInterface',
            'init',
            'lazyInstance'
        );

        $weaver->weaveClass(
            'Example\TestClass',
            array(
                $lazyWeaveInfo,
            ),
            '../generated/',
            'ClosureTestClassFactory'
        );

        $this->writeFactories($weaver);
    }


    /**
     * 
     */
    function testConst() {
        $timerWeaveInfo = new ExtendWeaveInfo('\Example\Proxy\ProxyWithConstant', null);

        $weaver = new Weaver();
        $weaver->weaveClass(
            'Example\TestClass',
            array(
                $timerWeaveInfo
            ),
            '../generated/',
            'ClosureTestClassFactory'
        );
     
         $this->writeFactories($weaver);
    }



    function writeFactories(Weaver $weaver) {
        //This always needs to be last
        $weaver->writeClosureFactories(
            '../generated/',
            'Example',
            'ClosureFactory',
            $weaver->getClosureFactories()
        );
    }
    
}




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

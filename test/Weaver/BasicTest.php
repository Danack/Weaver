<?php


namespace Weaver;

use Weaver\ExtendWeaveInfo;
use Weaver\MethodBinding;
use Weaver\ImplementsWeaveInfo;



class BasicTest extends \PHPUnit_Framework_TestCase {

    private $outputDir;
    
    function __construct() {
        $this->outputDir = dirname(__FILE__).'/../../generated/';
    }
    
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
            $this->outputDir,
            'ClosureTestClassFactory'
        );

        $this->writeFactories($weaver);
    }


    /**
     * 
     */
    function testInstanceWeave() {
        $weaver = new Weaver();

        $lazyWeaveInfo = new ImplementsWeaveInfo(
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
            $this->outputDir,
            'ClosureTestClassFactory'
        );

        $this->writeFactories($weaver);
    }


    /**
     *
     */
    function testFactoryInstanceWeave() {
        $weaver = new Weaver();

        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Weaver\Weave\LazyProxy',
            'TestInterface',
            'init',
            'lazyInstance',
            'SomeFactory', 'create'
        );

        $weaver->weaveClass(
            'Example\TestClass',
            array(
                $lazyWeaveInfo,
            ),
            $this->outputDir, 
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
            $this->outputDir,
            'ClosureTestClassFactory'
        );
     
         $this->writeFactories($weaver);
    }


    function writeFactories(Weaver $weaver) {
        //This always needs to be last
        $weaver->writeClosureFactories(
            $this->outputDir,
            'Example',
            'ClosureFactory',
            $weaver->getClosureFactories()
        );
    }
}

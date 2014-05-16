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

        /*
        $timerMethodBinding = new MethodBinding(
            new MethodMatcher(['executeQuery', 'noReturn']),
            '$this->timer->startTimer($this->queryString);',
            '$this->timer->stopTimer();'
        );

        $timerWeaveInfo = new ExtendWeaveInfo(
            'Example\TestClass',
            'Weaver\Weave\TimerProxy',
            [$timerMethodBinding]
        );

        $weaver = new ExtendWeaveGenerator($timerWeaveInfo);
        $previousClass = $weaver->writeClass($this->outputDir);

        $cacheMethodBinding = new MethodBinding(
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
        ); */

        $cacheWeaveInfo = new ExtendWeaveInfo(
            'Example\TestClass',
            'Weaver\Weave\CacheProxy',
            []
        );

        $weaver = new ExtendWeaveGenerator($cacheWeaveInfo);
        $weaver->writeClass($this->outputDir);
        
        /*
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

        $this->writeFactories($weaver); */
    }


    /**
     * 
     */
    function testInstanceWeave() {

        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Example\TestClass',
            'Weaver\Weave\LazyProxy',
            'TestInterface',
            'init',
            'lazyInstance' //This is not actually really used?
        );

        $weaver = new ImplementsWeaveGenerator($lazyWeaveInfo);
        $weaver->writeClass($this->outputDir);

        /*
        $weaver = new Weaver();
        $weaver->weaveClass(
            ,
            array(
                $lazyWeaveInfo,
            ),
            $this->outputDir,
            'ClosureTestClassFactory'
        );

        $this->writeFactories($weaver);
        
        */
    }


    /**
     *
     */
    function testFactoryInstanceWeave() {
        $weaver = new Weaver();

        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Example\TestClass',
            'Weaver\Weave\LazyProxy',
            'TestInterface',
            'init',
            'lazyInstance',
            'SomeFactory', 'create'
        );

        $weaver = new ImplementsWeaveGenerator($lazyWeaveInfo);
        $weaver->writeClass($this->outputDir);

/*        $weaver->weaveClass(
            'Example\TestClass',
            array(
                $lazyWeaveInfo,
            ),
            $this->outputDir
                //,           'ClosureTestClassFactory'
        );

*/

        //$this->writeFactories($weaver);
    }

    /**
     * 
     */
    function testConst() {

        $timerWeaveInfo = new ExtendWeaveInfo(
            'Example\TestClass',
            '\Example\Proxy\ProxyWithConstant', 
            []
        );

        $weaver = new ExtendWeaveGenerator($timerWeaveInfo);
        $weaver->writeClass($this->outputDir);
            
            
//        $weaver = new Weaver();
//        $weaver->weaveClass(
//            'Example\TestClass',
//            array(
//                $timerWeaveInfo
//            ),
//            $this->outputDir,
//            'ClosureTestClassFactory'
//        );
     
        // $this->writeFactories($weaver);
        
        
    }


    function writeFactories(Weaver $weaver) {
        
        /*
        //This always needs to be last
        $weaver->writeClosureFactories(
            $this->outputDir,
            'Example',
            'ClosureFactory',
            $weaver->getClosureFactories()
        );
        */
    }
}

<?php


namespace Weaver;


class ExtendWeaveTest extends \PHPUnit_Framework_TestCase {

    private $outputDir;
    
    function __construct() {
        $this->outputDir = dirname(__FILE__).'/../../generated/';
    }
    
    function testExtendWeave_cacheProxy() {

        $cacheWeaveInfo = new ExtendWeaveInfo(
            'Example\TestClass',
            'Weaver\Weave\CacheProxy',
            []
        );

        $weaver = new ExtendWeaveGenerator($cacheWeaveInfo);
        $weaver->writeClass($this->outputDir);
    }

    function testExtendWeave_TimerProxyCacheProxy() {

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
            $this->cache->put($cacheKey, $result, 50);'
        );

        
        $timerWeaveInfo = new ExtendWeaveInfo(
            $previousClass,
            'Weaver\Weave\CacheProxy',
            [$cacheMethodBinding]
        );

        $weaver = new ExtendWeaveGenerator($timerWeaveInfo);
        $previousClass = $weaver->writeClass($this->outputDir);

        $injector = createProvider([], []);

        $composite = $injector->make($previousClass, [':queryString' => 'testQueryString']);
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
        $injector = createProvider([], []);
        $original = $injector->make('Example\Proxy\ProxyWithConstant');
        $weaved =  $injector->make('Example\ProxyWithConstantXTestClass', [':queryString' => 'testQuery']);
        $this->assertEquals($original::A_CONSTANT, $weaved::A_CONSTANT);
    }
}

<?php


namespace Weaver;


class ExtendWeaveTest extends \PHPUnit_Framework_TestCase {

    private $outputDir;

    function __construct() {
        $this->outputDir = dirname(__FILE__).'/../../generated/';
    }

    function testExtendWeave_CacheProtoProxy() {
        $cacheMethodBinding = new MethodBinding(
            '__extend',
            new MethodMatcher(['getTweet'])
        );

        $cacheWeaveInfo = new ExtendWeaveInfo(
            'Example\Twitter',
            'Weaver\Weave\CacheProtoProxy',
            [$cacheMethodBinding]
        );

        $weaver = new ExtendWeaveGenerator($cacheWeaveInfo);
        $previousClass = $weaver->writeClass($this->outputDir, 'Example\CachedTwitter');
        $injector = createProvider([], []);
        $injector->defineParam('twitterKey', 123456);
        $composite = $injector->make($previousClass, [':queryString' => 'testQueryString']);
    }


    function testExtendWeave_TimerProxyCacheProxy() {

        $timerMethodBinding = new MethodBinding(
            '__extend',
            new MethodMatcher(['getTweet', 'pushTweet'])
        );

        $timerWeaveInfo = new ExtendWeaveInfo(
            'Example\Twitter',
            'Weaver\Weave\TimerProtoProxy',
            [$timerMethodBinding]
        );

        $weaver = new ExtendWeaveGenerator($timerWeaveInfo);
        $previousClass = $weaver->writeClass($this->outputDir, 'Example\TimedTwitter');
        $this->checkTweetResult($previousClass, 3);
        
        
        $cacheMethodBinding = new MethodBinding(
            '__extend',
            new MethodMatcher(['getTweet'])
        );

        $timerWeaveInfo = new ExtendWeaveInfo(
            $previousClass,
            'Weaver\Weave\CacheProtoProxy',
            [$cacheMethodBinding]
        );

        $weaver = new ExtendWeaveGenerator($timerWeaveInfo);
        $previousClass = $weaver->writeClass($this->outputDir, 'Example\CachedTimedTwitter');
        
        
        $this->checkTweetResult($previousClass, 2);

    }

    function checkTweetResult($classToMake, $expectedTimingEntries) {
        $injector = createProvider([], []);
        $injector->defineParam('twitterKey', 123456);
        $twitterApi = $injector->make($classToMake, [':queryString' => 'testQueryString']);
        
            /** @var $twitterApi \Example\TimedTwitter */
        $tweet1 = $twitterApi->getTweet("1234");
        $tweet2 = $twitterApi->getTweet("1234");
        $twitterApi->pushTweet("Hello there");
        $timings = $twitterApi->getTimings();
        $this->assertEquals($expectedTimingEntries, count($timings), "$classToMake should have $expectedTimingEntries timing events in it.");
    }

    function testExtendWeaveAllMethods() {
        $timerMethodBinding = new MethodBinding(
            '__extend',
            new MethodMatcher(['*'])
        );

        $timerWeaveInfo = new ExtendWeaveInfo(
            'Example\Twitter',
            'Weaver\Weave\TimerProtoProxy',
            [$timerMethodBinding]
        );

        $weaver = new ExtendWeaveGenerator($timerWeaveInfo);
        $previousClass = $weaver->writeClass($this->outputDir, 'Example\TimedTwitter');

        $injector = createProvider([], []);
        $injector->defineParam('twitterKey', 123456);
        $twitterApi = $injector->make($previousClass, [':queryString' => 'testQueryString']);
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

        $outputClassname = 'Example\Coverage\ProxyWithConstantXTestClass';

        $weaver = new ExtendWeaveGenerator($timerWeaveInfo);
        $weaver->writeClass($this->outputDir, $outputClassname);
        $injector = createProvider([], []);
        $original = $injector->make('Example\Proxy\ProxyWithConstant');
        $weaved =  $injector->make($outputClassname, [':queryString' => 'testQuery']);
        $this->assertEquals($original::A_CONSTANT, $weaved::A_CONSTANT);
    }
}

<?php


namespace Weaver;

use Intahwebz\Cache\InMemoryCache;
use Mockery;

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
            'Weaver\Weave\CacheProtoProxy',
            [$cacheMethodBinding]
        );

        $result = Weaver::weave('Example\Extend\Twitter', $cacheWeaveInfo);
        $result->writeFile($this->outputDir, 'Example\Extend\CachedTwitter');

        $twitterKey = "twitterKey12345";
        $tweetID = 12345;
        $tweetGetText = "This is tweet 12345";

        $cache = new InMemoryCache();
        $cachedTwitter = new \Example\Extend\CachedTwitter($twitterKey, $cache);

        $cachedTwitter = Mockery::mock($cachedTwitter)->makePartial();

        //Check that the param was passed to the base class correctly.
        $this->assertEquals($twitterKey, $cachedTwitter->getTwitterAPIKey());
        
        //Check that second call is cached
        $text1 = $cachedTwitter->getTweet($tweetID);
        $text2 = $cachedTwitter->getTweet($tweetID);

        $this->assertEquals(1, $cachedTwitter->getTweetCallCount(), "getTweetCall was not cached.");
        
        //Check that the return text is valid
        $this->assertEquals($tweetGetText, $text1);
        $this->assertEquals($tweetGetText, $text2);

        //Check that pushTweet is not cached.
        $cachedTwitter->pushTweet("Some text");
        $cachedTwitter->pushTweet("Some text");

        $this->assertEquals(2, $cachedTwitter->getPushTweetCallCount(), "getTweetCall was not cached.");
    }

//
//    function testExtendWeave_TimerProxyCacheProxy() {
//
//        $timerMethodBinding = new MethodBinding(
//            '__extend',
//            new MethodMatcher(['getTweet', 'pushTweet'])
//        );
//
//        $timerWeaveInfo = new ExtendWeaveInfo(
//            'Weaver\Weave\TimerProtoProxy',
//            [$timerMethodBinding]
//        );
//
//        $result = Weaver::weave('Example\Twitter', $timerWeaveInfo);
//        $previousClass = $result->writeFile($this->outputDir, 'Example\TimedTwitter');
//        $this->checkTweetResult($previousClass, 3);
//
//        $cacheMethodBinding = new MethodBinding(
//            '__extend',
//            new MethodMatcher(['getTweet'])
//        );
//
//        $cacheWeaveInfo = new ExtendWeaveInfo(
//            'Weaver\Weave\CacheProtoProxy',
//            [$cacheMethodBinding]
//        );
//
//        $result = Weaver::weave($previousClass, $cacheWeaveInfo);
//        $previousClass = $result->writeFile($this->outputDir, 'Example\CachedTimedTwitter');
//        $this->checkTweetResult($previousClass, 2);
//    }
//
//    function checkTweetResult($classToMake, $expectedTimingEntries) {
//        $injector = createProvider([], []);
//        $injector->defineParam('twitterKey', 123456);
//        $twitterApi = $injector->make($classToMake, [':queryString' => 'testQueryString']);
//        
//            /** @var $twitterApi \Example\TimedTwitter */
//        $tweet1 = $twitterApi->getTweet("1234");
//        $tweet2 = $twitterApi->getTweet("1234");
//        $twitterApi->pushTweet("Hello there");
//        $timings = $twitterApi->getTimings();
//        $this->assertEquals($expectedTimingEntries, count($timings), "$classToMake should have $expectedTimingEntries timing events in it.");
//    }



    function testExtendWeaveAllMethods() {
        $timerMethodBinding = new MethodBinding(
            '__extend',
            new MethodMatcher(['*'])
        );

        $timerWeaveInfo = new ExtendWeaveInfo(
            'Weaver\Weave\TimerProtoProxy',
            [$timerMethodBinding]
        );

        $result = Weaver::weave('Example\Extend\Twitter', $timerWeaveInfo);
        $outputClassname = $result->writeFile($this->outputDir, 'Example\Extend\TimedTwitter');

        $injector = createProvider([], []);
        $instance = $injector->make($outputClassname, [':twitterAPIKey' => 123456]);


        $instance->getTweet("12345");
        $instance->pushTweet("12345");
        $timings = $instance->getTimings();
        
        $this->assertEquals(2, count($timings));
    }


    function testExtendWeaveAllMethodsAndFactory() {
        $timerMethodBinding = new MethodBinding(
            '__extend',
            new MethodMatcher(['*'])
        );

        $timerWeaveInfo = new ExtendWeaveInfo(
            'Weaver\Weave\TimerProtoProxy',
            [$timerMethodBinding]
        );

        $result = Weaver::weave('Example\Extend\Twitter', $timerWeaveInfo);
        $outputClassname = $result->writeFile($this->outputDir, 'Example\Extend\TimedTwitter2');
        $factoryClassname = $result->writeFactory($this->outputDir);
        

        $injector = createProvider([], []);
        $factory = $injector->make($factoryClassname);

        $object = $factory->create('123456');

        $object->getTweet("12345");
        $object->pushTweet("12345");
        $timings = $object->getTimings();

        $this->assertEquals(2, count($timings));
    }
    


    /**
     * Check that constants in the decorating class are copied correctly.
     */
    function testConst() {
        $timerWeaveInfo = new ExtendWeaveInfo(
            '\Example\Extend\DecoratorWithConstant', 
            []
        );
        
        $outputClassname = 'Example\Extend\DecoratorWithConstantXTestClass';

        $result = Weaver::weave('Example\TestClass', $timerWeaveInfo);
        $result->writeFile($this->outputDir, $outputClassname);
        $weaved = new $outputClassname();
        $this->assertEquals(\Example\Extend\DecoratorWithConstant::A_CONSTANT, $weaved::A_CONSTANT);
    }

    protected function tearDown() {
        //\Mockery::close();
    }
}

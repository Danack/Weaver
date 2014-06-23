<?php


namespace Weaver;

use Mockery;


class ImplementWeaveTest extends \PHPUnit_Framework_TestCase {

    private $outputDir;
    
    function __construct() {
        $this->outputDir = dirname(__FILE__).'/../../generated/';
    }
//
//    function testMissingInterface() {
//        $this->setExpectedException('Weaver\WeaveException');
//        $lazyWeaveInfo = new LazyWeaveInfo(
//            'Weaver\Weave\LazyProxy',
//            'ThisInterfaceDoesNotExist'
//        );
//    }

    /**
     *
     */
//    function testImplementsWeaveMissingInterfaceException() {
//        
//        $this->setExpectedException('Weaver\WeaveException');
//        
//        $lazyWeaveInfo = new ImplementWeaveInfo(
//            'Weaver\Weave\NullClass',
//            'Example\TestInterface',
//            []
//        );
//
//        $result = Weaver::weave('Example\NullClass', $lazyWeaveInfo);
//        $classname = $result->writeFile($this->outputDir, 'Example\FirstImplement');
//    }

//TODO - make passing in an empty method binding, make it bind to all?
//OR just the interface properties.
//    function testSourceClassHasConstructor() {
//
//        $lazyWeaveInfo = new ImplementWeaveInfo(
//            'Example\Implement\ClassWithConstructor',
//            'Example\TestInterface',
//            new MethodMatcher(['executeQuery', 'anotherFunction'])
//        );
//
//        $outputClassName = 'Example\SourceClassHasConstructor';
//
//        $result = Weaver::weave('Example\TestClass', $lazyWeaveInfo);
//        $classname = $result->writeFile($this->outputDir, $outputClassName);
//
//        $decoratedInstance = new $classname("bar");
//
//        //   $initializerMatcher = $this->getMock($outputClassName, ['__invoke'], [$proxiedInstance]);
//    }


    function testSourceClassHasConstructor() {

        $cacheMethodBinding = new MethodBinding(
            '__extend',
            new MethodMatcher(['executeQuery', 'anotherFunction'])
        );
        
        $lazyWeaveInfo = new ImplementWeaveInfo(
            'Weaver\Weave\CacheProtoProxy',
            'Example\TestInterface',
            [$cacheMethodBinding]
        );

        $outputClassName = 'Example\SourceClassHasConstructor';
        
        $result = Weaver::weave('Example\Implement\ClassWithConstructor', $lazyWeaveInfo);
        $classname = $result->writeFile($this->outputDir, $outputClassName);

        //$decoratedInstance = new $classname("bar");

        $injector = createProvider([], []);
        $injector->defineParam('foo', 'bar');
        $decoratedInstance = $injector->make($classname);
    }

    function testImplementsWeaveCache() {

        $cacheMethodBinding = new MethodBinding(
            '__extend',
            new MethodMatcher(['executeQuery', 'anotherFunction'])
        );

        $cacheWeaveInfo = new ImplementWeaveInfo(
            'Weaver\Weave\CacheProtoProxy',
            'Example\TestInterface',
            [$cacheMethodBinding]
        );

        $outputClassName = 'Example\CachedImplement';

        $inputClassName = 'Example\TestClass';
        $result = Weaver::weave($inputClassName, $cacheWeaveInfo);
        $classname = $result->writeFile($this->outputDir, $outputClassName);

        //Setup Mocking
        $proxiedInstance = new \Example\TestClass("select * from someTable;");

        $mock = Mockery::mock($proxiedInstance);//, [$query]);
        $mock->shouldReceive('executeQuery')->once()->passthru();
        $mock->shouldReceive('__toString')->once();
        
        //Mock it all.
        $cache = new \Intahwebz\Cache\InMemoryCache();
        $decoratedObject = new $classname($mock, $cache);

        //This should go through to ClassToBeCached
        $decoratedObject->executeQuery(['foo' => 1]);
        //This should be cached
        $decoratedObject->executeQuery(['foo' => 1]);
        
        //This function shouldn't be decorated.
        //$decoratedObject->__toString();
    }


    function testInterfaceNotImplemented() {

        $this->setExpectedException(
            'Weaver\WeaveException',
            '',
            WeaveException::INTERFACE_NOT_IMPLEMENTED
        );

        $cacheMethodBinding = new MethodBinding(
            '__extend',
            new MethodMatcher(['executeQuery', 'anotherFunction'])
        );

        $cacheWeaveInfo = new ImplementWeaveInfo(
            'Weaver\Weave\CacheProtoProxy',
            'Example\TestInterface',
            [$cacheMethodBinding]
        );

        $outputClassName = 'Example\CachedImplement';

        $result = Weaver::weave('Example\Implement\ClassDoesntImplementInterface', $cacheWeaveInfo);
        $classname = $result->writeFile($this->outputDir, $outputClassName);

    }
    
    
}

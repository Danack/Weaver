<?php


namespace Weaver;

use Mockery;


class ImplementWeaveTest extends \PHPUnit_Framework_TestCase {

    private $outputDir;
    
    function __construct() {
        $this->outputDir = dirname(__FILE__).'/../../generated/';
    }


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

    function testClassFactoryIsAllowed() {

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

        $expensiveFactoryMethod = $result->generateFactory('\Example\Implement\ClassWithConstructorClosureFactory');

        $filename = $this->outputDir."implementClassFactoryIsAllowed.php";

        $fileHandle = fopen($filename, 'wb');
        fwrite($fileHandle, "<?php\n\n\n");
        fwrite($fileHandle, $expensiveFactoryMethod);
//        fwrite($fileHandle, "\n\n\n");
//        fwrite($fileHandle, $connectionFactoryMethod);
        fclose($fileHandle);
        
        require_once $filename;

        $injector = createProvider([], []);
        $injector->alias('Intahwebz\ObjectCache', 'Intahwebz\Cache\InMemoryCache');
        $classFactory = $injector->execute('createSourceClassHasConstructorFactory');
        
        $injector->defineParam('foo', 'bar');
        $injector->delegate($classname, [$classFactory, 'create']);

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
        $decoratedObject->__toString();
    }


    /**
     * Check that an appropriate exception is thrown when the class
     * to be decorated doesn't implement the interface.
     * @throws WeaveException
     */
    function testInterfaceNotImplemented() {

        $this->setExpectedException(
            'Weaver\WeaveException',
            '',
            WeaveException::INTERFACE_NOT_IMPLEMENTED
        );

        $cacheWeaveInfo = new ImplementWeaveInfo(
            'Weaver\Weave\CacheProtoProxy',
            'Example\TestInterface',
            []
        );

        $outputClassName = 'Example\CachedImplement';

        $result = Weaver::weave('Example\Implement\ClassDoesntImplementInterface', $cacheWeaveInfo);
        $classname = $result->writeFile($this->outputDir, $outputClassName);
        //TODO check result with Mockery
    }


    /**
     * Check that a typehinted param for the expensive class is created correctly.
     * @throws WeaveException
     */
    function testTypeHintedParameter() {
        $cacheMethodBinding = new MethodBinding(
            '__extend',
            new MethodMatcher(['executeQuery', 'anotherFunction'])
        );

        $cacheWeaveInfo = new ImplementWeaveInfo(
            'Weaver\Weave\CacheProtoProxy',
            'Example\Implement\ExpensiveInterface',
            [$cacheMethodBinding]
        );

        $result = Weaver::weave('Example\Implement\TestClassWithTypeHintedParameter', $cacheWeaveInfo);
        $className = $result->writeFile($this->outputDir, 'Example\Coverage\TypeHintedParam');

        $injector = createProvider([], []);
        $injector->defineParam('queryString', 'testQueryString');
        $proxiedClass = $injector->make($className);

        $proxiedClass->executeQuery('foo');
        //TODO add mock checking
        //check  Dependency is set
    }


    /**
     * @throws WeaveException
     */
    function testTypeHintedParameterWithOutputClassnameDefined() {

        $cacheMethodBinding = new MethodBinding(
            '__extend',
            new MethodMatcher(['executeQuery', 'anotherFunction'])
        );

        $cacheWeaveInfo = new ImplementWeaveInfo(
            'Weaver\Weave\CacheProtoProxy',
            'Example\Implement\ExpensiveInterface',
            [$cacheMethodBinding]
        );

        $outputClassName = 'Example\Coverage\Implement\ProxyWithDependency';

        $result = Weaver::weave('Example\Implement\TestClassWithTypeHintedParameter', $cacheWeaveInfo);
        $resultOutputClassName = $result->writeFile($this->outputDir, $outputClassName);

        $this->assertEquals($resultOutputClassName, $outputClassName);

        $injector = createProvider([], []);
        $injector->defineParam('dependencyNotInProxiedClass', true);
        $injector->defineParam('queryString', 'testQueryString');
        $proxiedClass = $injector->make($outputClassName, [':queryString' => 'testQueryString']);

        $factoryFunction = $result->generateFactory('Example\Lazy\StandardTestClassFactory');

        $fileHandle = fopen($this->outputDir."testTypeHintedParameterWithOutputClassnameDefined.php", 'wb');
        fwrite($fileHandle, "<?php\n");
        fwrite($fileHandle, $factoryFunction);
    }


    /**
     * Check that a typehinted param for the expensive class is created correctly.
     * @throws WeaveException
     */
    function testErrorWhenInterfaceNotString() {

        $this->setExpectedException(
            'Weaver\WeaveException',
            '',
            WeaveException::INTERFACE_NOT_SET
        );

        $cacheMethodBinding = new MethodBinding(
            '__extend',
            new MethodMatcher(['executeQuery', 'anotherFunction'])
        );

        $cacheWeaveInfo = new ImplementWeaveInfo(
            'Weaver\Weave\CacheProtoProxy',
            null,
            [$cacheMethodBinding]
        );
    }
}

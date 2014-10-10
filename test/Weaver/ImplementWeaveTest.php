<?php


namespace Weaver;

use Mockery;

use Weaver\MethodInterfaceMatcher;

class ImplementWeaveTest extends \PHPUnit_Framework_TestCase {

    private $outputDir;
    
    function __construct() {
        $this->outputDir = dirname(__FILE__).'/../../generated/';
    }

    /**
     * @throws WeaveException
     */
    function testSourceClassWithConstructor() {

        $cacheMethodBinding = new MethodBinding(
            '__extend',
            new MethodNameMatcher(['executeQuery', 'anotherFunction'])
        );
        
        $lazyWeaveInfo = new ImplementWeaveInfo(
            'Weaver\Weave\CachePrototypeDecorator',
            'Example\TestInterface',
            [$cacheMethodBinding]
        );

        $outputClassName = 'Example\Implement\SourceClassHasConstructor';
        
        $result = Weaver::weave('Example\Implement\ClassWithConstructor', $lazyWeaveInfo);
        $classname = $result->writeFile($this->outputDir, $outputClassName);

        $injector = createProvider([], []);
        $injector->defineParam('foo', 'bar');
        $decoratedInstance = $injector->make($classname);
    }



    function testSourceClassWithConstructorWithInterfaceMatcher() {

        $interfaceName = 'Example\TestInterface';
        
        $cacheMethodBinding = new MethodBinding(
            '__extend',
            new MethodInterfaceMatcher('Example\TestInterface')
        );

        $lazyWeaveInfo = new ImplementWeaveInfo(
            'Weaver\Weave\CachePrototypeDecorator',
            $interfaceName,
            [$cacheMethodBinding]
        );

        $outputClassName = 'Example\Implement\SourceClassHasConstructorWithInterfaceMatcher';

        $result = Weaver::weave('Example\Implement\ClassWithConstructor', $lazyWeaveInfo);
        $classname = $result->writeFile($this->outputDir, $outputClassName);

        $injector = createProvider([], []);
        $injector->defineParam('foo', 'bar');
        $decoratedInstance = $injector->make($classname);

        $this->assertInstanceOf($interfaceName, $decoratedInstance);
    }




    /**
     * @throws WeaveException
     */
    function testClassFactoryIsAllowed() {

        $cacheMethodBinding = new MethodBinding(
            '__extend',
            new MethodNameMatcher(['executeQuery', 'anotherFunction'])
        );

        $lazyWeaveInfo = new ImplementWeaveInfo(
            'Weaver\Weave\CachePrototypeDecorator',
            'Example\TestInterface',
            [$cacheMethodBinding]
        );

        $outputClassName = 'Example\Implement\SourceClassHasConstructor';

        $result = Weaver::weave('Example\Implement\ClassWithConstructor', $lazyWeaveInfo);
        $classname = $result->writeFile($this->outputDir, $outputClassName);

        $factoryClassname = $result->writeFactory($this->outputDir, $classname.'Factory');

        $injector = createProvider([], []);
        $injector->alias('Intahwebz\ObjectCache', 'Intahwebz\Cache\InMemoryCache');
        $factoryInstance = $injector->make($factoryClassname);

        $instance = $factoryInstance->create("bar");
        
        $this->assertInstanceOf($outputClassName, $instance);
        $this->assertInstanceOf('Example\TestInterface', $instance);
    }
    
    

//      //TODO - put closure factory generation back in, even though it's nuts.
//    
//            
//        if (false) {
//            $expensiveFactoryMethod = $result->generateClosureFactoryFunction('\Example\Implement\ClassWithConstructorClosureFactory'
//            );
//
//            $filename = $this->outputDir . "implementClassFactoryIsAllowed.php";
//
//            $fileHandle = fopen($filename, 'wb');
//            fwrite($fileHandle, "<?php\n\n\n");
//            fwrite($fileHandle, $expensiveFactoryMethod);
////        fwrite($fileHandle, "\n\n\n");
////        fwrite($fileHandle, $connectionFactoryMethod);
//            fclose($fileHandle);
//
//            require_once $filename;
//
//            $injector = createProvider([], []);
//            $injector->alias('Intahwebz\ObjectCache', 'Intahwebz\Cache\InMemoryCache');
//            $classFactory = $injector->execute('createSourceClassHasConstructorFactory');
//
//            $injector->defineParam('foo', 'bar');
//            $injector->delegate($classname, [$classFactory, 'create']);
//
//            $decoratedInstance = $injector->make($classname);
//
//        }


    function testImplementsWeaveCache() {

        $interfaceName = 'Example\TestInterface';
        
        $cacheMethodBinding = new MethodBinding(
            '__extend',
            new MethodNameMatcher(['executeQuery', 'anotherFunction'])
        );

        $cacheWeaveInfo = new ImplementWeaveInfo(
            'Weaver\Weave\CachePrototypeDecorator',
            $interfaceName,
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

        $this->assertInstanceOf($interfaceName, $decoratedObject);
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
            'Weaver\Weave\CachePrototypeDecorator',
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

        $interfaceName = 'Example\Implement\ExpensiveInterface';
        
        $cacheMethodBinding = new MethodBinding(
            '__extend',
            new MethodNameMatcher(['executeQuery', 'anotherFunction'])
        );

        $cacheWeaveInfo = new ImplementWeaveInfo(
            'Weaver\Weave\CachePrototypeDecorator',
            $interfaceName,
            [$cacheMethodBinding]
        );

        $result = Weaver::weave('Example\Implement\TestClassWithTypeHintedParameter', $cacheWeaveInfo);
        $className = $result->writeFile($this->outputDir, 'Example\Coverage\TypeHintedParam');

        $injector = createProvider([], []);
        $injector->defineParam('queryString', 'testQueryString');
        $decoratedClass = $injector->make($className);

        $decoratedClass->executeQuery('foo');
        //TODO add mock checking
        //check  Dependency is set

        $this->assertInstanceOf($interfaceName, $decoratedClass);
    }


    /**
     * @throws WeaveException
     */
    function testTypeHintedParameterWithOutputClassnameDefined() {

        $cacheMethodBinding = new MethodBinding(
            '__extend',
            new MethodNameMatcher(['executeQuery', 'anotherFunction'])
        );

        $cacheWeaveInfo = new ImplementWeaveInfo(
            'Weaver\Weave\CachePrototypeDecorator',
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

        $factoryFunction = $result->generateClosureFactoryFunction('Example\Lazy\StandardTestClassFactory');

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
            new MethodNameMatcher(['executeQuery', 'anotherFunction'])
        );

        $cacheWeaveInfo = new ImplementWeaveInfo(
            'Weaver\Weave\CachePrototypeDecorator',
            null,
            [$cacheMethodBinding]
        );
    }

    function testInterfaceMatcher() {
        $interfaceMatcher = new MethodInterfaceMatcher('Weaver\MethodInterfaceMatcher');
        $this->assertTrue($interfaceMatcher->matches('matches'), "Method that should exist not found.");
        $this->assertFalse($interfaceMatcher->matches('thatches'), "Method found that shouldn't exist");
    }


    
}

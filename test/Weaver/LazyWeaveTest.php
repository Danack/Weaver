<?php


namespace Weaver;

use Mockery;

class ImplementsWeaveTest extends \PHPUnit_Framework_TestCase {

    private $outputDir;
    
    function __construct() {
        $this->outputDir = dirname(__FILE__).'/../../generated/';
    }



    /**
     *
     */
    function testImplementsWeaveWithoutFactory() {
        $lazyWeaveInfo = new LazyWeaveInfo(
            'Weaver\Weave\LazyProxy',
            '\Example\Lazy\ExpensiveInterface'
        );

        $result = Weaver::weave('Example\Lazy\ExpensiveClass', $lazyWeaveInfo);
        $classname = $result->writeFile($this->outputDir, 'Example\Coverage\LazyProxyXTestClass');

        $mock = Mockery::mock($classname);
//        $mock->shouldReceive('executeQuery')->once()->passthru();
//        $mock->shouldReceive('__toString')->once();
    }




//    /**
//     * @throws WeaveException
//     */
//    function testInstanceWeaveIntegration() {
//        $timerMethodBinding = new MethodBinding(
//            '__extend',
//            new MethodMatcher(['execute', 'fetch', 'sendBigString', 'sendFile'])
//        );
//
//        $timerWeaveInfo = new ExtendWeaveInfo(
//            'Weaver\Weave\TimerProtoProxy',
//            [$timerMethodBinding]
//        );
//
//        $timedSQLIStatementWeave = Weaver::weave('Example\MySQLiStatement', $timerWeaveInfo);
//        $classname = $timedSQLIStatementWeave->writeFile($this->outputDir, 'Example\TimedPreparedStatement');
//        $statementFactoryMethod = $timedSQLIStatementWeave->generateFactory('Example\ClosureStatementFactory');
//
//        $lazyWeaveInfo = new LazyWeaveInfo(
//            'Weaver\Weave\LazyProxy',    //The decorating class
//            'Example\Connection'//,      //The interface to expose TODO allow multiple interfaces
//        );
//
//        $result = Weaver::weave('Example\MySQLiConnection', $lazyWeaveInfo);
//        $classname = $result->writeFile($this->outputDir, 'Example\LazyMySQLConnection');
//        $connectionFactoryMethod = $result->generateFactory('Example\ClosureConnectionFactory');
//
//
//        $filename = $this->outputDir."createFactory.php";
//
//        $fileHandle = fopen($filename, 'wb');
//        fwrite($fileHandle, "<?php\n\n\n");
//        fwrite($fileHandle, $statementFactoryMethod);
//        fwrite($fileHandle, "\n\n\n");
//        fwrite($fileHandle, $connectionFactoryMethod);
//        fclose($fileHandle);
//
//        require_once $filename;
//
//        $injector = createProvider([], []);
//
//        $dbParams = array(
//            ':host'     => '127.0.0.1',
//            ':username' => 'username',
//            ':password' => '12345',
//            ':port'     => 3306,
//            ':socket'   => null
//        );
//
//        $injector->delegate('Example\ClosureConnectionFactory', 'createLazyMySQLConnectionFactory');
//        $injector->delegate('Example\Connection', ['Example\ClosureConnectionFactory', 'create'], $dbParams);
//        $injector->delegate('Example\StatementFactory', 'createTimedPreparedStatementFactory');
//        
//        $injector->make('Example\Connection');
//    }






    function testFunctionFactoryIsAllowed() {
        $lazyWeaveInfo = new LazyWeaveInfo(
            'Weaver\Weave\LazyProxy',
            'Example\Lazy\ExpensiveInterface',
            'createTestClass'
        );

        $result = Weaver::weave('Example\Lazy\ExpensiveClass', $lazyWeaveInfo);
        $classname = $result->writeFile($this->outputDir, 'Example\Coverage\FunctionFactory');
        //TODO - write the factory method
        //TODO - or mock the factory call.
    }


    /**
     * Check that a typehinted param for the expensive class is created correctly.
     * @throws WeaveException
     */
    function testTypeHintedParameter() {
        $lazyWeaveInfo = new LazyWeaveInfo(
            'Example\Lazy\LazyProxyWithDependency',
            'Example\Lazy\ExpensiveInterface',
            'makeIt',
            'lazyObject'
        );

        $result = Weaver::weave('Example\Lazy\TestClassWithTypeHintedParameter', $lazyWeaveInfo);
        $className = $result->writeFile($this->outputDir, 'Example\Coverage\TypeHintedParam');

        $injector = createProvider([], []);
        $proxiedClass = $injector->make($className, [':queryString' => 'testQueryString']);

        $proxiedClass->executeQuery('foo');
        //TODO add mock checking
    }


    /**
     * @throws WeaveException
     */
    function testTypeHintedParameterWithOutputClassnameDefined() {
        $lazyWeaveInfo = new LazyWeaveInfo(
            'Example\Lazy\LazyProxyWithDependencyNamedDependency',
            'Example\Lazy\ExpensiveInterface'
        );

        $outputClassName = 'Example\Coverage\Lazy\ProxyWithDependency';

        $result = Weaver::weave('Example\Lazy\TestClassWithTypeHintedParameter', $lazyWeaveInfo);
        $resultOutputClassName = $result->writeFile($this->outputDir, $outputClassName);

        $this->assertEquals($resultOutputClassName, $outputClassName);

        $injector = createProvider([], []);
        $injector->defineParam('dependencyNotInProxiedClass', true);
        $proxiedClass = $injector->make($outputClassName, [':queryString' => 'testQueryString']);

        $factoryFunction = $result->generateFactory('Example\Lazy\StandardTestClassFactory');

        $fileHandle = fopen($this->outputDir."testTypeHintedParameterWithOutputClassnameDefined.php", 'wb');
        fwrite($fileHandle, "<?php\n");
        fwrite($fileHandle, $factoryFunction);
    }


    /**
     * Check that passing a initMethodName that is not a string throws an exception
     */
    function testThrowExceptionBadInitMethod() {
        $this->setExpectedException('Weaver\WeaveException');
        
        $lazyWeaveInfo = new LazyWeaveInfo(
            'Example\Lazy\LazyProxyWithDependencyNamedDependency',
            'Example\Lazy\ExpensiveInterface',
            new \StdClass()
        );
    }

    /**
     * Check that passing something other than a string for the lazyPropertyName
     * throws an exception.
     */
    function testThrowExceptionBadLazyProperty() {
        $this->setExpectedException('Weaver\WeaveException');
        $lazyWeaveInfo = new LazyWeaveInfo(
            'Example\Lazy\LazyProxyWithDependencyNamedDependency',
            'Example\Lazy\ExpensiveInterface',
            'init',
            new \StdClass()
        );
    }

    /**
     * Test that trying to use an interface that doesn't exist throws the
     * appropriate extension.
     */
    function testMissingInterface() {
        $this->setExpectedException('Weaver\WeaveException');
        $lazyWeaveInfo = new LazyWeaveInfo(
            'Weaver\Weave\LazyProxy',
            'ThisInterfaceDoesNotExist'
        );
    }

}

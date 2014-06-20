<?php


namespace Weaver;


class ImplementWeaveTest extends \PHPUnit_Framework_TestCase {

    private $outputDir;
    
    function __construct() {
        $this->outputDir = dirname(__FILE__).'/../../generated/';
    }

//    function testMissingInterface() {
//        $this->setExpectedException('Weaver\WeaveException');
//        $lazyWeaveInfo = new LazyWeaveInfo(
//            'Weaver\Weave\LazyProxy',
//            'ThisInterfaceDoesNotExist'
//        );
//    }

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
//        //$connectionFactory = createLazyProxyXMySQLiConnectionFactory();
//        //$provider->delegate(Intahwebz\DB\Connection::class, [$connectionFactory, 'create'], $dbParams);
//
//        $injector->delegate('Example\ClosureConnectionFactory', 'createLazyMySQLConnectionFactory');
//        $injector->delegate('Example\Connection', ['Example\ClosureConnectionFactory', 'create'], $dbParams);
//        $injector->delegate('Example\StatementFactory', 'createTimedPreparedStatementFactory');
//        
//        $injector->make('Example\Connection');
//    }




    /**
     *
     */
    function testImplementsWeaveWithoutFactory() {
        $lazyWeaveInfo = new ImplementWeaveInfo(
            'Weaver\Weave\NullClass',
            'Example\TestInterface',
            []
        );

        $result = Weaver::weave('Example\TestClass', $lazyWeaveInfo);
        $classname = $result->writeFile($this->outputDir, 'Example\FirstImplement');
    }


//    function testFunctionFactoryIsAllowed() {
//        $lazyWeaveInfo = new LazyWeaveInfo(
//            'Weaver\Weave\LazyProxy',
//            'Example\TestInterface',
//            'createTestClass'
//        );
//
//        $result = Weaver::weave('Example\TestClass', $lazyWeaveInfo);
//        $classname = $result->writeFile($this->outputDir, 'Example\Coverage\FunctionFactory');
//        //TODO - write the factory method
//    }
//
//    function testTypeHintedParameter() {
//        $lazyWeaveInfo = new LazyWeaveInfo(
//            'Example\LazyProxyWithDependency',
//            'Example\TestInterface',
//            'makeIt',
//            'lazyObject'
//        );
//
//        $result = Weaver::weave('Example\TestClassWithTypeHintedParameter', $lazyWeaveInfo);
//        $className = $result->writeFile($this->outputDir, 'Example\Coverage\TypeHintedParam');
//
//        $injector = createProvider([], []);
//        $proxiedClass = $injector->make($className, [':queryString' => 'testQueryString']);
//    }
//
//
//    function testTypeHintedParameterWithOutputClassnameDefined() {
//        $lazyWeaveInfo = new LazyWeaveInfo(
//            'Example\LazyProxyWithDependencyNamedDependency',
//            'Example\TestInterface'
//        );
//
//        $outputClassName = 'Example\Coverage\ProxyWithDependency';
//
//        $result = Weaver::weave('Example\TestClassWithTypeHintedParameter', $lazyWeaveInfo);
//        $resultOutputClassName = $result->writeFile($this->outputDir, $outputClassName);
//
//        $this->assertEquals($resultOutputClassName, $outputClassName);
//
//        $injector = createProvider([], []);
//        $injector->defineParam('dependencyNotInProxiedClass', true);
//        $proxiedClass = $injector->make($outputClassName, [':queryString' => 'testQueryString']);
//
//        $factoryFunction = $result->generateFactory('Example\StandardTestClassFactory');
//
//        //TODO - eval this?
//        $fileHandle = fopen($this->outputDir."testTypeHintedParameterWithOutputClassnameDefined.php", 'wb');
//        fwrite($fileHandle, "<?php\n");
//        fwrite($fileHandle, $factoryFunction);
//    }
//
//
//    function testThrowExceptionBadInitMethod() {
//        $this->setExpectedException('Weaver\WeaveException');
//        
//        $lazyWeaveInfo = new LazyWeaveInfo(
//            'Example\LazyProxyWithDependencyNamedDependency',
//            'Example\TestInterface',
//            new \StdClass()
//        );
//    }
//
//    function testThrowExceptionBadLazyProperty() {
//        $this->setExpectedException('Weaver\WeaveException');
//        $lazyWeaveInfo = new LazyWeaveInfo(
//            'Example\LazyProxyWithDependencyNamedDependency',
//            'Example\TestInterface',
//            'init',
//            new \StdClass()
//        );
//    }
}

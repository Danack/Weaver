<?php


namespace Weaver;

use Weaver\ExtendWeaveInfo;
use Weaver\MethodBinding;
use Weaver\ImplementsWeaveInfo;



class ImplementsWeaveTest extends \PHPUnit_Framework_TestCase {

    private $outputDir;
    
    function __construct() {
        $this->outputDir = dirname(__FILE__).'/../../generated/';
    }

    function testMissingInterface() {
        $this->setExpectedException('Weaver\WeaveException');
        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Weaver\Weave\LazyProxy',
            'ThisInterfaceDoesNotExist'
        );
    }

    function testInstanceWeave() {

        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Weaver\Weave\LazyProxy',    //The decorating class
            'Example\DBConnection',      //The interface to expose TODO allow multiple interfaces
            ['Example\DBConnectionFactory', 'create'], //Optional factory method to use to create instances
            'init',                      //Optional, what to call the init method, default init
            'lazyInstance'               //Optional, what to call the instance property, default 'lazyInstance'
        );

        $result = Weaver::weave('Example\MySQLConnection', $lazyWeaveInfo);
        $classname = $result->writeFile($this->outputDir, 'Example\LazyMySQLConnection');

////TODO - eval this?
//        $fileHandle = fopen($this->outputDir."testInstanceWeave.php", 'wb');
//        fwrite($fileHandle, "<?php\n");
//        fwrite($fileHandle, $closureFactoryInfo->__toString());
//        fclose($fileHandle);        
    }

    /**
     *
     */
    function testImplementsWeaveWithoutFactory() {
        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Weaver\Weave\LazyProxy',
            'Example\TestInterface'
        );

        $result = Weaver::weave('Example\TestClass', $lazyWeaveInfo);
        $classname = $result->writeFile($this->outputDir, 'Example\Coverage\LazyProxyXTestClass');
    }


    function testFunctionFactoryIsAllowed() {
        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Weaver\Weave\LazyProxy',
            'Example\TestInterface',
            'createTestClass'
        );

        $result = Weaver::weave('Example\TestClass', $lazyWeaveInfo);
        $classname = $result->writeFile($this->outputDir, 'Example\Coverage\FunctionFactory');
        //TODO - write the factory method
    }


    function testInvalidFactory() {
        $this->setExpectedException('Weaver\WeaveException');

        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Weaver\Weave\LazyProxy',
            'Example\TestInterface',
            new \stdClass()//not a valid factory
        );

        $result = Weaver::weave('Example\TestClass', $lazyWeaveInfo);
        $result->writeFile($this->outputDir);
    }

    
    function testTypeHintedParameter() {
        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Example\LazyProxyWithDependency',
            'Example\TestInterface'
        );

        $result = Weaver::weave('Example\TestClassWithTypeHintedParameter', $lazyWeaveInfo);
        $className = $result->writeFile($this->outputDir, 'Example\Coverage\TypeHintedParam');

        $injector = createProvider([], []);
        $proxiedClass = $injector->make($className, [':queryString' => 'testQueryString']);
    }


    function testTypeHintedParameterWithOutputClassnameDefined() {
        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Example\LazyProxyWithDependencyNamedDependency',
            'Example\TestInterface'
        );

        $outputClassName = 'Example\Coverage\ProxyWithDependency';

        $result = Weaver::weave('Example\TestClassWithTypeHintedParameter', $lazyWeaveInfo);
        $resultOutputClassName = $result->writeFile($this->outputDir, $outputClassName);

        $this->assertEquals($resultOutputClassName, $outputClassName);

        $injector = createProvider([], []);
        $injector->defineParam('dependencyNotInProxiedClass', true);
        $proxiedClass = $injector->make($outputClassName, [':queryString' => 'testQueryString']);

        $factoryFunction = $result->generateFactory('Example\StandardTestClassFactory');

        //TODO - eval this?
        $fileHandle = fopen($this->outputDir."testTypeHintedParameterWithOutputClassnameDefined.php", 'wb');
        fwrite($fileHandle, "<?php\n");
        fwrite($fileHandle, $factoryFunction);
    }
}

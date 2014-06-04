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
            'Example\TestClass',
            'Weaver\Weave\LazyProxy',
            'ThisInterfaceDoesNotExist'
        );
    }

    
    
    /**
     * 
     */
    function testInstanceWeave() {

        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Example\MySQLConnection',  //The class to proxy
            'Weaver\Weave\LazyProxy',    //The decorating class
            'Example\DBConnection',      //The interface to expose TODO allow multiple interfaces
            ['Example\DBConnectionFactory', 'create'], //Optional factory method to use to create instances
            'init',                      //Optional, what to call the init method, default init
            'lazyInstance'               //Optional, what to call the instance property, default 'lazyInstance'
        );

        $weaver = new ImplementsWeaveGenerator($lazyWeaveInfo);
        $previousClass = $weaver->writeClass($this->outputDir, 'Example\LazyMySQLConnection');

        /*
        $closureFactoryInfo = $weaver->generateClosureFactoryInfo('Example\StandardTestClassFactory');
        $injector = createProvider([], []);

        //TODO - eval this?
        $fileHandle = fopen($this->outputDir."testInstanceWeave.php", 'wb');
        fwrite($fileHandle, "<?php\n");
        fwrite($fileHandle, $closureFactoryInfo->__toString());
        fclose($fileHandle);
        
        require_once($this->outputDir."testInstanceWeave.php");
*/
    }

    /**
     *
     */
    function testImplementsWeaveWithoutFactory() {
        $weaver = new Weaver();

        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Example\TestClass',
            'Weaver\Weave\LazyProxy',
            'Example\TestInterface'
        );

        $weaver = new ImplementsWeaveGenerator($lazyWeaveInfo);
        $weaver->writeClass($this->outputDir, 'Example\Coverage\LazyProxyXTestClass');
    }


    function testFunctionFactoryIsAllowed() {
        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Example\TestClass',
            'Weaver\Weave\LazyProxy',
            'Example\TestInterface',
            'createTestClass'//not a valid factory
        );

        $weaver = new ImplementsWeaveGenerator($lazyWeaveInfo);
        $weaver->writeClass($this->outputDir, 'Example\Coverage\FunctionFactory');
    }


    function testInvalidFactory() {
        $this->setExpectedException('Weaver\WeaveException');

        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Example\TestClass',
            'Weaver\Weave\LazyProxy',
            'Example\TestInterface',
            new \stdClass()//not a valid factory
        );

        $weaver = new ImplementsWeaveGenerator($lazyWeaveInfo);
    }

    
    function testTypeHintedParameter() {
        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Example\TestClassWithTypeHintedParameter',
            'Example\LazyProxyWithDependency',
            'Example\TestInterface'
        );

        $weaver = new ImplementsWeaveGenerator($lazyWeaveInfo);
        $className = $weaver->writeClass($this->outputDir, 'Example\Coverage\TypeHintedParam');

        $injector = createProvider([], []);
        $proxiedClass = $injector->make($className, [':queryString' => 'testQueryString']);
    }


    function testTypeHintedParameterWithOutputClassnameDefined() {
        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Example\TestClassWithTypeHintedParameter',
            'Example\LazyProxyWithDependencyNamedDependency',
            'Example\TestInterface'
        );

        $weaver = new ImplementsWeaveGenerator($lazyWeaveInfo);
        $outputClassName = 'Example\Coverage\ProxyWithDependency';
        $resultOutputClassName = $weaver->writeClass($this->outputDir, $outputClassName);

        $this->assertEquals($resultOutputClassName, $outputClassName);

        $injector = createProvider([], []);
        $injector->defineParam('dependencyNotInProxiedClass', true);
        $proxiedClass = $injector->make($outputClassName, [':queryString' => 'testQueryString']);
        $closureFactoryInfo = $weaver->generateClosureFactoryInfo('Example\StandardTestClassFactory');

        //TODO - eval this?
        $fileHandle = fopen($this->outputDir."testTypeHintedParameterWithOutputClassnameDefined.php", 'wb');
        fwrite($fileHandle, "<?php\n");
        fwrite($fileHandle, $closureFactoryInfo->__toString());
    }
}

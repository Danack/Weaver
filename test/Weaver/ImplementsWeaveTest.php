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
            'ThisInterfaceDoesNotExist',
            'init',
            'lazyInstance', //This is not actually really used?
            ['Example\TestClassFactory', 'create']
        );
        
    }
    
    
    /**
     * 
     */
    function testInstanceWeave() {

        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Example\TestClass',
            'Weaver\Weave\LazyProxy',
            'Example\TestInterface',
            'init',
            'lazyInstance', //This is not actually really used?
            ['Example\TestClassFactory', 'create']
        );

        $weaver = new ImplementsWeaveGenerator($lazyWeaveInfo);
        $previousClass = $weaver->writeClass($this->outputDir);

        
        $closureFactoryInfo = $weaver->generateClosureFactoryInfo('Example\StandardTestClassFactory');
        $injector = createProvider([], []);

        //TODO - eval this?
        $fileHandle = fopen($this->outputDir."testInstanceWeave.php", 'wb');
        fwrite($fileHandle, "<?php\n");
        fwrite($fileHandle, $closureFactoryInfo->__toString());
        fclose($fileHandle);
        
        require_once($this->outputDir."testInstanceWeave.php");

        $injector->delegate('Example\TestClassFactory', $closureFactoryInfo->getFunctionName());
        $proxiedClass = $injector->make($previousClass, [':queryString' => 'testQueryString']);
    }

    /**
     *
     */
    function testFactoryInstanceWeave() {
        $weaver = new Weaver();

        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Example\TestClass',
            'Weaver\Weave\LazyProxy',
            'Example\TestInterface',
            'init',
            'lazyInstance'
            // dont set a factory ['Example\TestClassFactory', 'create']
        );

        $weaver = new ImplementsWeaveGenerator($lazyWeaveInfo);
        $weaver->writeClass($this->outputDir);
    }


    function testFunctionFactory() {
        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Example\TestClass',
            'Weaver\Weave\LazyProxy',
            'Example\TestInterface',
            'init',
            'lazyInstance',
            'createTestClass'//not a valid factory
        );

        $weaver = new ImplementsWeaveGenerator($lazyWeaveInfo);
        //TODO - need to be able to set output class name
        $weaver->writeClass($this->outputDir);
    }
    
    

    function testInvalidFactory() {

        $this->setExpectedException('Weaver\WeaveException');

        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Example\TestClass',
            'Weaver\Weave\LazyProxy',
            'Example\TestInterface',
            'init',
            'lazyInstance',
            new \stdClass()//not a valid factory
        );

        $weaver = new ImplementsWeaveGenerator($lazyWeaveInfo);
        //$weaver->writeClass($this->outputDir);
    }


    function testTypeHintedParameter() {
        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Example\TestClassWithTypeHintedParameter',
            'Example\LazyProxyWithDependency',
            'Example\TestInterface',
            'init',
            'lazyInstance'
        );

        $weaver = new ImplementsWeaveGenerator($lazyWeaveInfo);
        $className = $weaver->writeClass($this->outputDir);

        $injector = createProvider([], []);
        $proxiedClass = $injector->make($className, [':queryString' => 'testQueryString']);
    }


    function testTypeHintedParameterWithOutputClassnameDefined() {
        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Example\TestClassWithTypeHintedParameter',
            'Example\LazyProxyWithDependencyNamedDependency',
            'Example\TestInterface',
            'init',
            'lazyInstance'
        );

        $weaver = new ImplementsWeaveGenerator($lazyWeaveInfo);
        $outputClassName = 'Example\ProxyWithDependency';
        $resultOutputClassName = $weaver->writeClass($this->outputDir, $outputClassName);

        $this->assertEquals($resultOutputClassName, $outputClassName);

        $injector = createProvider([], []);
        
        $injector->defineParam('dependencyNotInProxiedClass', true);
        
        $proxiedClass = $injector->make($outputClassName, [':queryString' => 'testQueryString']);

        $closureFactoryInfo = $weaver->generateClosureFactoryInfo('Example\StandardTestClassFactory');
        //$injector = createProvider([], []);

        //TODO - eval this?
        $fileHandle = fopen($this->outputDir."testTypeHintedParameterWithOutputClassnameDefined.php", 'wb');
        fwrite($fileHandle, "<?php\n");
        fwrite($fileHandle, $closureFactoryInfo->__toString());
        
        
    }
    
    
}

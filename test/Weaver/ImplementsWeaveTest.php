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

    /**
     * 
     */
    function testInstanceWeave() {

        $lazyWeaveInfo = new ImplementsWeaveInfo(
            'Example\TestClass',
            'Weaver\Weave\LazyProxy',
            'TestInterface',
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
            'TestInterface',
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
            'TestInterface',
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
            'TestInterface',
            'init',
            'lazyInstance',
            new \stdClass()//not a valid factory
        );

        $weaver = new ImplementsWeaveGenerator($lazyWeaveInfo);
        //$weaver->writeClass($this->outputDir);
    }
    

}

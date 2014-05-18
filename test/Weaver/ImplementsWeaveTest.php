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
            'lazyInstance' //This is not actually really used?
        );

        $weaver = new ImplementsWeaveGenerator($lazyWeaveInfo);
        $previousClass = $weaver->writeClass($this->outputDir);


        $injector = createProvider([], []);

        $composite = $injector->make($previousClass, [':queryString' => 'testQueryString']);
        
        /*
        $weaver = new Weaver();
        $weaver->weaveClass(
            ,
            array(
                $lazyWeaveInfo,
            ),
            $this->outputDir,
            'ClosureTestClassFactory'
        );

        $this->writeFactories($weaver);
        
        */
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
            'lazyInstance',
            'SomeFactory', 'create'
        );

        $weaver = new ImplementsWeaveGenerator($lazyWeaveInfo);
        $weaver->writeClass($this->outputDir);
        
        

/*        $weaver->weaveClass(
            'Example\TestClass',
            array(
                $lazyWeaveInfo,
            ),
            $this->outputDir
            //  'ClosureTestClassFactory'
        );

*/

        //$this->writeFactories($weaver);
    }



}

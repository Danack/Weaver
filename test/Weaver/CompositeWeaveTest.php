<?php


namespace Weaver;

use Weaver\ExtendWeaveInfo;
use Weaver\MethodBinding;
use Weaver\ImplementsWeaveInfo;



class CompositeWeaveTest extends \PHPUnit_Framework_TestCase {

    private $outputDir;
    
    function __construct() {
        $this->outputDir = dirname(__FILE__).'/../../generated/';
    }
    
    function testCompositeWeave() {

        $components = [
            'Example\Component1',
            'Example\Component2'
        ];

        $compositeWeaveInfo = new \Weaver\CompositeWeaveInfo(
            'Example\CompositeHolder',
            $components,
            [
                'renderElement' => 'string',
//                'getParams' => 'array',
            ]
        );
        $weaveMethod = new CompositeWeaveGenerator($compositeWeaveInfo);
        $weaveMethod->writeClass($this->outputDir);

        $injector = createProvider([], []);
        
        $injector->defineParam('component1Arg', 'foo');
        $injector->defineParam('component2Arg', 'bar');
        
        $composite = $injector->make('Example\CompositeHolderComponent1Component2');

        $output = $composite->render();
        
        $this->assertContains('component1', $output);
        $this->assertContains('component2', $output);
        $this->assertNotContains('CompositeHolder', $output);
    }
}

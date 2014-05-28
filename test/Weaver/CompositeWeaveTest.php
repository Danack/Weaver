<?php


namespace Weaver;


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
            ]
        );
        $weaveMethod = new CompositeWeaveGenerator($compositeWeaveInfo);
        
        $weaveMethod->writeClass($this->outputDir);
        

        $injector = createProvider([], []);
        
        $injector->defineParam('component1Arg', 'foo');
        $injector->defineParam('component2Arg', 'bar');
        $injector->defineParam('testValue', 5);
        
        
        $composite = $injector->make('Example\CompositeHolderComponent1Component2');

        $output = $composite->render();
        
        $this->assertContains('component1', $output);
        $this->assertContains('component2', $output);
        $this->assertNotContains('CompositeHolder', $output);
    }


    function testCompositeWeaveSpecificName() {

        $components = [
            'Example\Component1',
            'Example\Component2'
        ];

        $compositeWeaveInfo = new \Weaver\CompositeWeaveInfo(
            'Example\CompositeHolder',
            $components,
            [
                'renderElement' => 'string',
            ]
        );
        $weaveMethod = new CompositeWeaveGenerator($compositeWeaveInfo);
        $weaveMethod->writeClass($this->outputDir, 'Example\CompositeRenamed');

        $injector = createProvider([], []);

        $injector->defineParam('component1Arg', 'foo');
        $injector->defineParam('component2Arg', 'bar');
        $injector->defineParam('testValue', 5);

        $composite = $injector->make('Example\CompositeRenamed');
    }

    function testCompositeWeaveGlobalNamespaceClassname() {

        $components = [
            'Example\Component1',
            'Example\Component2'
        ];

        $compositeWeaveInfo = new \Weaver\CompositeWeaveInfo(
            'Example\CompositeHolder',
            $components,
            [
                'renderElement' => 'string',
            ]
        );
        $weaveMethod = new CompositeWeaveGenerator($compositeWeaveInfo);
        $weaveMethod->writeClass($this->outputDir, 'GlobalNamespaceCompositeRenamed');

        $injector = createProvider([], []);

        $injector->defineParam('component1Arg', 'foo');
        $injector->defineParam('component2Arg', 'bar');
        $injector->defineParam('testValue', 5);

        $composite = $injector->make('GlobalNamespaceCompositeRenamed');
    }


    function testCompositeWeaveArrayReturn() {

        $components = [
            'Example\ComponentParams1',
            'Example\ComponentParams2'
        ];

        $compositeWeaveInfo = new \Weaver\CompositeWeaveInfo(
            'Example\CompositeParamsHolder',
            $components,
            [
                'getParams' => 'array',
            ]
        );
        $weaveMethod = new CompositeWeaveGenerator($compositeWeaveInfo);
        $classname = $weaveMethod->writeClass($this->outputDir);
        $injector = createProvider([], []);
        $composite = $injector->make($classname);
    }
    
    function testUnknownCompositeType() {

        $this->setExpectedException('Weaver\WeaveException');

        $components = [
            'Example\ComponentParams1',
            'Example\ComponentParams2'
        ];

        $compositeWeaveInfo = new \Weaver\CompositeWeaveInfo(
            'Example\CompositeParamsHolder',
            $components,
            [
                'getParams' => 'blob',
            ]
        );
        $weaveMethod = new CompositeWeaveGenerator($compositeWeaveInfo);
        $classname = $weaveMethod->writeClass($this->outputDir);
    }
    
}

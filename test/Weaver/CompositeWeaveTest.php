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
            [
                'renderElement' => 'string',
            ]
        );

        $outputClassname = 'Example\Coverage\CompositeHolderComponent1Component2';

        $result = Weaver::weave($components, $compositeWeaveInfo);
        $result->writeFile($this->outputDir, $outputClassname);
        
        $injector = createProvider([], []);
        
        $injector->defineParam('component1Arg', 'foo');
        $injector->defineParam('component2Arg', 'bar');
        $injector->defineParam('testValue', 5);

        $composite = $injector->make($outputClassname);
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
            [
                'renderElement' => 'string',
            ]
        );

        $result = Weaver::weave($components, $compositeWeaveInfo);
        $result->writeFile($this->outputDir, 'Example\Coverage\CompositeRenamed');

        $injector = createProvider([], []);

        $injector->defineParam('component1Arg', 'foo');
        $injector->defineParam('component2Arg', 'bar');
        $injector->defineParam('testValue', 5);

        $composite = $injector->make('Example\Coverage\CompositeRenamed');
    }

    function testCompositeWeaveGlobalNamespaceClassname() {

        $components = [
            'Example\Component1',
            'Example\Component2'
        ];

        $compositeWeaveInfo = new \Weaver\CompositeWeaveInfo(
            'Example\CompositeHolder',
            [
                'renderElement' => 'string',
            ]
        );

        $result = Weaver::weave($components, $compositeWeaveInfo);
        $result->writeFile($this->outputDir, 'GlobalNamespaceTest');

        $injector = createProvider([], []);

        $injector->defineParam('component1Arg', 'foo');
        $injector->defineParam('component2Arg', 'bar');
        $injector->defineParam('testValue', 5);

        $composite = $injector->make('GlobalNamespaceTest');
    }


    function testCompositeWeaveArrayReturn() {
        $components = [
            'Example\ComponentParams1',
            'Example\ComponentParams2'
        ];

        $compositeWeaveInfo = new \Weaver\CompositeWeaveInfo(
            'Example\CompositeParamsHolder',
            [
                'getParams' => 'array',
            ]
        );

        $result = Weaver::weave($components, $compositeWeaveInfo);
        $classname = $result->writeFile($this->outputDir, 'Example\Coverage\ArrayReturn');

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
            [
                'getParams' => 'blob',
            ]
        );

        $result = Weaver::weave($components, $compositeWeaveInfo);
        $classname = $result->writeFile($this->outputDir, 'Example\Coverage\UnknownComposite');
    }
}

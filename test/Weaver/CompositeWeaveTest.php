<?php


namespace Weaver;

use Mockery;

class CompositeWeaveTest extends \PHPUnit_Framework_TestCase {

    private $outputDir;
    
    function __construct() {
        $this->outputDir = dirname(__FILE__).'/../../generated/';
    }

    /**
     * @throws WeaveException
     */
    function testCompositeWeave() {

        $components = [
            'Example\Composite\Component1',
            'Example\Composite\Component2'
        ];

        //TODO - allow this to be generated from the interface
        $compositeWeaveInfo = new \Weaver\CompositeWeaveInfo(
            'Example\Composite\CompositeHolder',
            ['render' => CompositeWeaveInfo::RETURN_STRING,]
        );

        $outputClassname = 'Example\Coverage\Composite\TestCompositeWeave';

        $result = Weaver::weave($components, $compositeWeaveInfo);
        $result->writeFile($this->outputDir, $outputClassname);

        $injector = createProvider([], []);

        //Setup Test objects
        $component1 = $injector->make('Example\Composite\Component1');
        $component2 = $injector->make('Example\Composite\Component2');

        $component1 = Mockery::mock($component1);
        $component1->shouldReceive('render')->once()->passthru();
        $component1->shouldReceive('methodNotInInterface')->never();

        $component2 = Mockery::mock($component2);
        $component2->shouldReceive('render')->once()->passthru();
        $component2->shouldReceive('methodNotInInterface')->never();

       
        $compositeSUT = new $outputClassname($component1, $component2, 5);

        //Run test
        $result = $compositeSUT->render();
        $compositeSUT->methodNotInInterface();
        
        //Check results
        $this->assertContains("component1", $result);
        $this->assertContains("component2", $result);
    }


    /**
     * Test that the auto-generated name is usable 
     * @throws WeaveException
     */
    function testCompositeWeaveGeneratedName() {

        $components = [
            'Example\Composite\Component1',
            'Example\Composite\Component2'
        ];

        $compositeWeaveInfo = new \Weaver\CompositeWeaveInfo(
            'Example\Composite\CompositeHolder',
            [
                'render' => CompositeWeaveInfo::RETURN_STRING,
            ]
        );

        $result = Weaver::weave($components, $compositeWeaveInfo);
        $outputClassname = $result->writeFile($this->outputDir);

        $injector = createProvider([], []);
        $injector->defineParam('testValue', 5);
        $composite = $injector->make($outputClassname);


        $result = $composite->render();

        //Check results
        $this->assertContains("component1", $result);
        $this->assertContains("component2", $result);
    }

    /**
     * Check that Weaving a class into global namespace works correctly.
     * @throws WeaveException
     */
    function testCompositeWeaveGlobalNamespaceClassname() {

        $components = [
            'Example\Composite\Component1',
            'Example\Composite\Component2'
        ];

        $compositeWeaveInfo = new \Weaver\CompositeWeaveInfo(
            'Example\Composite\CompositeHolder',
            [
                'render' => CompositeWeaveInfo::RETURN_STRING,
            ]
        );

        $weaveResult = Weaver::weave($components, $compositeWeaveInfo);
        $weaveResult->writeFile($this->outputDir, 'GlobalNamespaceTest');

        $injector = createProvider([], []);
        $composite = $injector->make('GlobalNamespaceTest', [':testValue' => 5]);

        $result = $composite->render();

        //Check results
        $this->assertContains("component1", $result);
        $this->assertContains("component2", $result);
    }


    /**
     
     * @throws WeaveException
     */
    function testCompositeWeaveArrayReturn() {
        $components = [
            'Example\Composite\ComponentParams1',
            'Example\Composite\ComponentParams2'
        ];

        $compositeWeaveInfo = new \Weaver\CompositeWeaveInfo(
            'Example\Composite\CompositeParamsHolder',
            [
                'getParams' => CompositeWeaveInfo::RETURN_ARRAY
            ]
        );

        $result = Weaver::weave($components, $compositeWeaveInfo);
        $outputClassname = $result->writeFile($this->outputDir, 'Example\Coverage\ArrayReturn');


        $injector = createProvider([], []);
        $component1 = $injector->make('Example\Composite\ComponentParams1');
        $component2 = $injector->make('Example\Composite\ComponentParams2');

        $component1 = Mockery::mock($component1);
        $component1->shouldReceive('getParams')->once()->andReturn(['foo1' => 1]);
        $component2 = Mockery::mock($component2);
        $component2->shouldReceive('getParams')->once()->andReturn(['foo2' => 2]);

        $compositeSUT = new $outputClassname($component1, $component2);

        $result = $compositeSUT->getParams();

        $this->assertArrayHasKey('foo1', $result);
        $this->assertArrayHasKey('foo2', $result);
    }

    /**
     * Check that an unknown return type throws the correct exception.
     * @throws WeaveException
     */
    function testUnknownCompositeType() {
        $this->setExpectedException('Weaver\WeaveException');

        $components = [
            'Example\Composite\Component1',
            'Example\Composite\Component2'
        ];

        $compositeWeaveInfo = new \Weaver\CompositeWeaveInfo(
            'Example\Composite\CompositeHolder',
            [
                'getParams' => 'UnknownReturnType',
            ]
        );

        $result = Weaver::weave($components, $compositeWeaveInfo);
        $classname = $result->writeFile($this->outputDir, 'Example\Coverage\UnknownComposite');
    }

    protected function tearDown() {
        \Mockery::close();
    }
}

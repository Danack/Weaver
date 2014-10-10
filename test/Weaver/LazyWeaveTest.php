<?php


namespace Weaver;

use Mockery;

class ImplementsWeaveTest extends \PHPUnit_Framework_TestCase {

    private $outputDir;
    
    function __construct() {
        $this->outputDir = dirname(__FILE__).'/../../generated/';
    }

    /**
     *
     */
    function testImplementsWeaveWithoutFactory() {

        $interfaceName = 'Example\Lazy\ExpensiveInterface';
        
        $lazyWeaveInfo = new LazyWeaveInfo(
            $interfaceName
        );

        $result = Weaver::weave('Example\Lazy\ExpensiveClass', $lazyWeaveInfo);
        $classname = $result->writeFile($this->outputDir, 'Example\Coverage\LazyProxyXTestClass');

        $mock = Mockery::mock($classname);
        $this->assertInstanceOf($interfaceName, $mock);
    }

    /**
     *
     */
    function testLazyWeaveWithPropertyNameSet() {

        $interfaceName = 'Example\Lazy\ExpensiveInterface';
        
        $lazyWeaveInfo = new LazyWeaveInfo(
            $interfaceName,
            'makeIt',
            'delayedInstance'
        );

        $result = Weaver::weave('Example\Lazy\ExpensiveClass', $lazyWeaveInfo);
        $classname = $result->writeFile(
            $this->outputDir,
            'Example\Coverage\LazyWeaveWithPropertyNameSet'
        );

        $mock = Mockery::mock($classname);
        $this->assertInstanceOf($interfaceName, $mock);
    }


    function testFunctionFactoryIsAllowed() {
        $lazyWeaveInfo = new LazyWeaveInfo(
            //'Weaver\Weave\LazyProxy',
            'Example\Lazy\ExpensiveInterface',
            'createTestClass'
        );

        $result = Weaver::weave('Example\Lazy\ExpensiveClass', $lazyWeaveInfo);
        $classname = $result->writeFile($this->outputDir, 'Example\Coverage\FunctionFactory');
        //TODO - write the factory method
        //TODO - or mock the factory call.
        
        
    }


    /**
     * Check that passing a initMethodName that is not a string throws an exception
     */
    function testThrowExceptionBadInitMethod() {
        $this->setExpectedException(
            'Weaver\WeaveException',
            '',
            WeaveException::METHOD_NAME_INVALID
        );
        
        $lazyWeaveInfo = new LazyWeaveInfo(
            'Example\Lazy\ExpensiveInterface',
            new \StdClass()
        );
    }

    /**
     * Check that passing something other than a string for the lazyPropertyName
     * throws an exception.
     */
    function testThrowExceptionBadLazyProperty() {
        $this->setExpectedException(
            'Weaver\WeaveException',
            '',
            WeaveException::PROPERTY_NAME_INVALID
        );
        $lazyWeaveInfo = new LazyWeaveInfo(
            'Example\Lazy\ExpensiveInterface',
            'init',
            new \StdClass()
        );
    }

    /**
     * Test that trying to use an interface that doesn't exist throws the
     * appropriate extension.
     */
    function testMissingInterface() {
        $this->setExpectedException(
            'Weaver\WeaveException',
            '',
            WeaveException::INTERFACE_NOT_VALID
        );
        
        $lazyWeaveInfo = new LazyWeaveInfo(
            'ThisInterfaceDoesNotExist'
        );
    }
}

<?php


namespace Weaver;

use Danack\Code\Generator\DocBlockGenerator;
use Danack\Code\Generator\MethodGenerator;
use Danack\Code\Generator\ParameterGenerator;
use Danack\Code\Generator\PropertyGenerator;
use Danack\Code\Reflection\MethodReflection;
use Danack\Code\Reflection\ClassReflection;


abstract class SingleClassWeaveGenerator {
    
    /**
     * @var \Danack\Code\Generator\ClassGenerator
     */
    protected $generator;

    /**
     * @var ClassReflection
     */
    protected $sourceReflection;

    /**
     * @var ClassReflection
     */
    protected $decoratorReflection;

    /**
     * @return string
     */
    function getNamespaceName() {
        return $this->sourceReflection->getNamespaceName();
    }

    /**
     * * Generates a name that indicates what the class is composed of.
     * e.g. A DB class decorated with Timer would be TimerXDB
     * 
     * @return string
     */
    function generateWeavedName() {
        return $this->decoratorReflection->getShortName()."X".$this->sourceReflection->getShortName();
    }


    /**
     * addPropertiesAndConstantsFromReflection - does what it's name says.
     * 
     * @param ClassReflection $classReflection
     * @internal param $originalSourceClass
     */
    function addPropertiesAndConstantsFromReflection(ClassReflection $classReflection ) {
        $constants = $classReflection->getConstants();
        foreach ($constants as $name => $value) {
            $this->generator->addProperty($name, $value, PropertyGenerator::FLAG_CONSTANT);
        }

        $properties = $classReflection->getProperties();
        foreach ($properties as $property) {
            $newProperty = PropertyGenerator::fromReflection($property);
            $newProperty->setVisibility(\Danack\Code\Generator\AbstractMemberGenerator::FLAG_PRIVATE);
            $this->generator->addPropertyFromGenerator($newProperty);
        }
    }

    /**
     * Directly add the methods from the decorating class. They are not modified.
     * The 'special' functions __construct, __prototype and __extend are not copied
     * as they are instead used to generate decorated methods.
     * 
     * @return null|MethodReflection
     */
    function addDecoratorMethods() {
        $methods = $this->decoratorReflection->getMethods();

        foreach ($methods as $method) {
            $name = $method->getName();

            if ($name === '__construct' || $name === '__prototype' || $name == '__extend') {
                continue;
            }

            $methodGenerator = MethodGenerator::fromReflection($method);
            $this->generator->addMethodFromGenerator($methodGenerator);
        }
    }

    /**
     * @return string
     */
    function getFQCN() {
        $namespace = $this->getNamespaceName();
        $classname = $this->generateWeavedName();

        $return = $classname;

        if (strlen($namespace)) {
            $return = $namespace.'\\'.$return;
        }

        return $return;
    }
}

 
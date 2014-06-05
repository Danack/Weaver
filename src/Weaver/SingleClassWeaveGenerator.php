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
     * @return string
     */
    function getProxiedName() {
        return $this->decoratorReflection->getShortName()."X".$this->sourceReflection->getShortName();
    }


    /**
     * @param ClassReflection $classReflection
     * @param $originalSourceClass
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
        $classname = $this->getProxiedName();

        $return = $classname;

        if (strlen($namespace)) {
            $return = $namespace.'\\'.$return;
        }

        return $return;
    }
}

 
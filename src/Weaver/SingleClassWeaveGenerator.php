<?php


namespace Weaver;

use Danack\Code\Generator\DocBlockGenerator;
use Danack\Code\Generator\MethodGenerator;
use Danack\Code\Generator\ParameterGenerator;
use Danack\Code\Generator\PropertyGenerator;
use Danack\Code\Reflection\MethodReflection;
use Danack\Code\Reflection\ClassReflection;


abstract class SingleClassWeaveGenerator implements WeaveGenerator {

    /**
     * @var \Danack\Code\Generator\ClassGenerator
     */
    protected $generator;

    /**
     * @var ClassReflection
     */
    protected $sourceReflector;

    /**
     * @var ClassReflection
     */
    protected $decoratorReflector;

//    /**
//     * @param MethodReflection $methodReflection
//     * @return mixed
//     */
//    abstract function generateProxyMethodBody(MethodReflection $methodReflection);

    /**
     * @return string
     */
    function getNamespaceName() {
        return $this->sourceReflector->getNamespaceName();
    }

    /**
     * @return string
     */
    function getProxiedName() {
        return $this->decoratorReflector->getShortName()."X".$this->sourceReflector->getShortName();
    }



    /**
     * @param ClassReflection $reflector
     * @param $originalSourceClass
     */
    function addPropertiesAndConstantsForReflector(ClassReflection $reflector ) {
        $constants = $reflector->getConstants();
        foreach ($constants as $name => $value) {
            $this->generator->addProperty($name, $value, PropertyGenerator::FLAG_CONSTANT);
        }

        $properties = $reflector->getProperties();
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
        $methods = $this->decoratorReflector->getMethods();

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

 
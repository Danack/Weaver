<?php


namespace Weaver;

use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Reflection\MethodReflection;
use Zend\Code\Reflection\ClassReflection;


abstract class SingleClassWeaveGenerator implements WeaveGenerator {

    /**
     * @var \Zend\Code\Generator\ClassGenerator
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

    /**
     * @param MethodReflection $methodReflection
     * @return mixed
     */
    abstract function generateProxyMethodBody(MethodReflection $methodReflection);

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
     * @return null|MethodReflection
     */
    function addProxyMethods() {
        $methods = $this->sourceReflector->getMethods();

        foreach ($methods as $method) {
            $name = $method->getName();

            if ($name == '__construct') {
                continue;
            }

            $methodGenerator = MethodGenerator::fromReflection($method);
            $newBody = $this->generateProxyMethodBody($method);

            if ($newBody) {
                //TODO - document why this is only added when newBody is set.
                $methodGenerator->setBody($newBody);
                $this->generator->addMethodFromGenerator($methodGenerator);
            }
        }
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
            $newProperty->setVisibility(\Zend\Code\Generator\AbstractMemberGenerator::FLAG_PRIVATE);
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

            if ($name == '__construct') {
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

 
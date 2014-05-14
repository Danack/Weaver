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

            $parameters = $method->getParameters();
            $docBlock = $method->getDocBlock();

            if ($docBlock) {
                $docBlock = DocBlockGenerator::fromReflection($docBlock);
            }

            $generatedParameters = array();

            foreach ($parameters as $reflectionParameter) {
                $generatedParameters[] = ParameterGenerator::fromReflection($reflectionParameter);
            }

            $newBody = $this->generateProxyMethodBody($method);

            if ($newBody) {
                $this->generator->addMethod(
                    $name,
                    $generatedParameters,
                    MethodGenerator::FLAG_PUBLIC,
                    $newBody,
                    $docBlock
                );
            }
        }
    }

    /**
     * Adds the properties and constants from the decorating class to the
     * class being weaved.
     * @param $originalSourceClass
     */
    function addPropertiesAndConstants() {
        $this->addPropertiesAndConstantsForReflector($this->decoratorReflector);
        $this->addPropertiesAndConstantsForReflector($this->sourceReflector);
    }

    /**
     * @param ClassReflection $reflector
     * @param $originalSourceClass
     */
    function addPropertiesAndConstantsForReflector(ClassReflection $reflector ) {
        $originalSourceClass = $reflector->getShortName();
        $constants = $reflector->getConstants();
        foreach ($constants as $name => $value) {
            $this->generator->addProperty($name, $value, PropertyGenerator::FLAG_CONSTANT);
        }

        $properties = $reflector->getProperties();
        foreach ($properties as $property) {
            $newProperty = PropertyGenerator::fromReflection($property);
            $newProperty->setDocBlock(" @var \\$originalSourceClass");
            $this->generator->addPropertyFromGenerator($newProperty);
        }
    }

    /**
     * @param $originalSourceClass
     * @param $constructorParameters
     * @param $closureFactoryName
     * @param MethodReflection $sourceConstructorMethod
     * @param MethodReflection $decoratorConstructorMethod
     * @return string
     */

    /*
    function generateFactoryClosure(
        $originalSourceClass,
        $constructorParameters,
        $closureFactoryName,
        MethodReflection $sourceConstructorMethod = null,
        MethodReflection $decoratorConstructorMethod = null
    ) {

        $fqcn = $this->getFQCN();

        if ($decoratorConstructorMethod != null) {
            $parameters = $decoratorConstructorMethod->getParameters();
            foreach ($parameters as $reflectionParameter) {
                $generatedParameters[] = ParameterGenerator::fromReflection($reflectionParameter);
            }
        }

        $className = '\\'.$fqcn;

        $addedParameters = $this->getAddedParameters($originalSourceClass, $constructorParameters);

        $originalSourceReflection = new ClassReflection($originalSourceClass);
        $originalConstructorParameters = array();
        $originalConstructor = $originalSourceReflection->getConstructor();

        if ($originalConstructor) {
            $originalConstructorParameters = $originalConstructor->getParameters();
        }

        $allParams = getConstructorParamsString($constructorParameters);




        $decoratorParamsWithType = getConstructorParamsString($addedParameters, true);

        $decoratorUseParams = '';
        if (count($addedParameters)) {
            $decoratorUseParams = 'use ('.getConstructorParamsString($addedParameters).')';
        }

        $objectParams = getConstructorParamsString($originalConstructorParameters);

        $createClosureFactoryName = 'create'.$this->getProxiedName().'Factory';

        $function = <<< END
function $createClosureFactoryName($decoratorParamsWithType) {

    \$closure = function ($objectParams) $decoratorUseParams {
        \$object = new $className(
            $allParams
        );

        return \$object;
    };

    return new $closureFactoryName(\$closure);
}

END;
        return $function;
    }
    
    */


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

            $parameters = $method->getParameters();
            $generatedParameters = array();

            foreach ($parameters as $reflectionParameter) {
                $generatedParameters[] = ParameterGenerator::fromReflection($reflectionParameter);
            }

            $this->generator->addMethod(
                $name,
                $generatedParameters,
                MethodGenerator::FLAG_PUBLIC,
                $method->getBody(),
                $method->getDocBlock()
            );
        }
    }

    /**
     * @param $originalSourceClass
     * @param $constructorParameters MethodReflection[]
     * @return array
     */
    function getAddedParameters($originalSourceClass, $constructorParameters) {
        $originalSourceReflection = new ClassReflection($originalSourceClass);
        $sourceConstructorParameters = array();
        $constructor = $originalSourceReflection->getConstructor();

        if ($constructor) {
            $sourceConstructorParameters = $constructor->getParameters();
        }

        $addedParameters = array();

        if (is_array($constructorParameters) == false) {
            throw new \ErrorException("Constructor params needs to be an array, for some reason it isn't.");
        }

        foreach ($constructorParameters as $constructorParameter) {
            $presentInOriginal = false;

            foreach ($sourceConstructorParameters as $sourceConstructorParameter) {
                if ($constructorParameter->getName() == $sourceConstructorParameter->getName()) {
                    $presentInOriginal = true;
                }
            }

            if ($presentInOriginal == false) {
                $addedParameters[] = $constructorParameter;
            }
        }

        return $addedParameters;
    }

    /**
     * @return string
     */
    function getFQCN() {
        $namespace = $this->getNamespaceName();
        $classname = $this->getProxiedName();

        if (strlen($namespace)) {
            return $namespace.'\\'.$classname;
        }
        else {
            return $classname;
        }
    }
}

 
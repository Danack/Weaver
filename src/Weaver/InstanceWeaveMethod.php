<?php


namespace Weaver;


use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

use Zend\Code\Reflection\MethodReflection;
use Zend\Code\Reflection\ParameterReflection;
use Zend\Code\Reflection\ClassReflection;




class InstanceWeaveMethod extends AbstractWeaveMethod  {

    /**
     * @param $sourceClass
     * @param $decoratorClass
     * @param $weaving
     * @param $savePath
     * @throws \RuntimeException
     */
    function __construct($sourceClass, $decoratorClass, $weaving, $savePath) {

        $this->sourceReflector = new ClassReflection($sourceClass);
        $this->decoratorReflector = new ClassReflection($decoratorClass);
        $this->generator = new ClassGenerator();
        $this->weaving = $weaving;
    }
    
    function generate($savePath, $originalSourceClass) {
        $sourceConstructorMethod = $this->addProxyMethods(self::LAZY);
        $decoratorConstructorMethod = $this->addDecoratorMethods();
        $constructorParameters = $this->addInitMethod($sourceConstructorMethod, $decoratorConstructorMethod);
        $this->addPropertiesAndConstants();
        $this->saveFile($savePath);

        $factoryClosure = $this->generateFactoryClosure($originalSourceClass, $constructorParameters, $sourceConstructorMethod, $decoratorConstructorMethod);

        return $factoryClosure;
    }




    function addLazyConstructor(
        MethodReflection $sourceConstructorMethod = null,
        MethodReflection $decoratorConstructorMethod = null
    ) {

        $constructorBody = '';
        $constructorParams = '';
        $copyBody = '';

        $generatedParameters = array();

        if ($sourceConstructorMethod != null) {
            $parameters = $sourceConstructorMethod->getParameters();
            $separator = '';

            foreach ($parameters as $reflectionParameter) {
                $generatedParameters[] = ParameterGenerator::fromReflection($reflectionParameter);
                $constructorParams .= $separator.'$this->'.$reflectionParameter->getName();
                $separator = ', ';

                $this->generator->addProperty($reflectionParameter->getName());

                $copyBody .= '$this->'.$reflectionParameter->getName().' = $'.$reflectionParameter->getName().";\n";
            }
        }

        if ($decoratorConstructorMethod != null) {
            $parameters = $decoratorConstructorMethod->getParameters();
            foreach ($parameters as $reflectionParameter) {
                $generatedParameters[] = ParameterGenerator::fromReflection($reflectionParameter);
            }

            $constructorBody .= $decoratorConstructorMethod->getBody();
        }

        $constructorBody .= $copyBody;

        $this->generator->addMethod(
            '__construct',
            $generatedParameters,
            MethodGenerator::FLAG_PUBLIC,
            $constructorBody,
            ""
        );

        return $constructorParams;
    }


    function addInitMethod(MethodReflection $sourceConstructorMethod, MethodReflection $decoratorConstructorMethod) {
        $initBody = 'if ($this->'.$this->weaving['lazyProperty'].' == null) {
            $this->lazyInstance = new \\'.$this->sourceReflector->getName().'(';

        $constructorParams = $this->addLazyConstructor($sourceConstructorMethod,
            $decoratorConstructorMethod);

        $initBody .= $constructorParams;

        $initBody .= ");\n}";

        $this->generator->addMethod(
            'init',
            array(),
            MethodGenerator::FLAG_PUBLIC,
            $initBody,
            ""
        );
        
        return $constructorParams;
    }

    /**
     * @param $weavingInfo
     * @param MethodReflection $method
     * @return string
     */
    function generateProxyMethodBody(MethodReflection $method, $weavingInfo) {
        $newBody = '';
        $newBody .= '$this->'.$this->weaving['init']."();\n";
        $newBody .= '$result = $this->'.$this->weaving['lazyProperty'].'->'.$method->getName()."(";
        $parameters = $method->getParameters();
        $separator = '';

        foreach ($parameters as $reflectionParameter) {
            $newBody .= $separator.'$'.$reflectionParameter->getName();
            $separator = ', ';
        }

        $newBody .= ");\n";
        $newBody .= 'return $result;'."\n";

        return $newBody;
    }

}

 
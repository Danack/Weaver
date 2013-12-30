<?php


namespace Weaver;

use Zend\Code\Generator\ClassGenerator;

use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Reflection\MethodReflection;
use Zend\Code\Reflection\ClassReflection;

class ExtendWeaveMethod extends AbstractWeaveMethod {


    /**
     * @param $sourceClass
     * @param $decoratorClass
     * @param $methodBindingArray
     * @internal param \Weaver\MethodBinding[] $methodBinding
     */
    function __construct($sourceClass, $decoratorClass, $methodBindingArray) {
        $this->sourceReflector = new ClassReflection($sourceClass);
        $this->decoratorReflector = new ClassReflection($decoratorClass);
        $this->generator = new ClassGenerator();
        $this->methodBindingArray = $methodBindingArray;
        $this->setupClassName();
    }

    function generate($savePath, $originalSourceClass) {
        $sourceConstructorMethod = $this->addProxyMethods();
        $decoratorConstructorMethod = $this->addDecoratorMethods();
        $constructorParameters = $this->addProxyConstructor($sourceConstructorMethod, $decoratorConstructorMethod);
        $this->addPropertiesAndConstants($originalSourceClass);
        $this->saveFile($savePath);

        $factoryClosure = $this->generateFactoryClosure($originalSourceClass, $constructorParameters, $sourceConstructorMethod, $decoratorConstructorMethod);

        return $factoryClosure;
    }



    function addProxyConstructor(
        MethodReflection $sourceConstructorMethod = null, 
        MethodReflection $decoratorConstructorMethod = null) {
        $constructorBody = '';

        $generatedParameters = array();

        if ($sourceConstructorMethod != null) {
            $parameters = $sourceConstructorMethod->getParameters();

            $constructorBody .= 'parent::__construct(';

            $separator = '';

            foreach ($parameters as $reflectionParameter) {
                $generatedParameters[] = ParameterGenerator::fromReflection($reflectionParameter);
                $constructorBody .= $separator.'$'.$reflectionParameter->getName();
                $separator = ', ';
            }

            $constructorBody .= ");\n";
        }

        if ($decoratorConstructorMethod != null) {
            $parameters = $decoratorConstructorMethod->getParameters();
            foreach ($parameters as $reflectionParameter) {
                $generatedParameters[] = ParameterGenerator::fromReflection($reflectionParameter);
            }

            $constructorBody .= $decoratorConstructorMethod->getBody();
        }

        $this->generator->addMethod(
            '__construct',
            $generatedParameters,
            MethodGenerator::FLAG_PUBLIC,
            $constructorBody,
            ""
        );

        return $generatedParameters;
    }



    function generateProxyMethodBody(MethodReflection $method) {
        $name = $method->getName();

        $methodBinding = $this->getMethodBindingForMethod($name);
        
        if (!$methodBinding) {
            return false;
        }

        $newBody = '';
        
        $beforeFunction = $methodBinding->getBefore();
        
        if ($beforeFunction) {
            $newBody .= $beforeFunction."\n";
        }
        
        $newBody .= '$result = parent::'.$method->getName()."(";
        $parameters = $method->getParameters();
        $separator = '';

        foreach ($parameters as $reflectionParameter) {
            $newBody .= $separator.'$'.$reflectionParameter->getName();
            $separator = ', ';
        }

        $newBody .= ");\n";

        $afterFunction = $methodBinding->getAfter();

        if ($afterFunction) {
            $newBody .= $afterFunction."\n\n";
        }

        $newBody .= 'return $result;'."\n";

        return $newBody;
    }

}

 
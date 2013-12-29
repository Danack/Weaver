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





class ExtendWeaveMethod extends AbstractWeaveMethod {

    function __construct($sourceClass, $decoratorClass, $weaving) {
        $this->sourceReflector = new ClassReflection($sourceClass);
        $this->decoratorReflector = new ClassReflection($decoratorClass);
        $this->generator = new ClassGenerator();
        $this->weaving = $weaving;
        $this->setupClassName();
    }

    function generate($savePath, $originalSourceClass) {
        $sourceConstructorMethod = $this->addProxyMethods();
        $decoratorConstructorMethod = $this->addDecoratorMethods();
        $constructorParameters = $this->addProxyConstructor($sourceConstructorMethod, $decoratorConstructorMethod);
        $this->addPropertiesAndConstants();
        $this->saveFile($savePath);

        $factoryClosure = $this->generateFactoryClosure($originalSourceClass, $constructorParameters, $sourceConstructorMethod, $decoratorConstructorMethod);

        return $factoryClosure;
    }



    function addProxyConstructor(MethodReflection $sourceConstructorMethod, MethodReflection $decoratorConstructorMethod) {
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


    function generateProxyMethodBody(MethodReflection $method, $weavingInfo) {
        $name = $method->getName();

        if (array_key_exists($name, $this->weaving) == false) {
            return false;
        }

        $weavingInfo = $this->weaving[$name];

        $newBody = '';
        
        if (array_key_exists('before', $weavingInfo) == true) {
            $newBody .= $weavingInfo['before']."\n";
        }
        
        $newBody .= '$result = parent::'.$method->getName()."(";
        $parameters = $method->getParameters();
        $separator = '';

        foreach ($parameters as $reflectionParameter) {
            $newBody .= $separator.'$'.$reflectionParameter->getName();
            $separator = ', ';
        }

        $newBody .= ");\n";

        if (array_key_exists('after', $weavingInfo) == true) {
            $newBody .= $weavingInfo['after']."\n\n";
        }

        $newBody .= 'return $result;'."\n";

        return $newBody;
    }

}

 
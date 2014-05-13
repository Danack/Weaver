<?php


namespace Weaver;

use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Reflection\MethodReflection;
use Zend\Code\Reflection\ClassReflection;



abstract class SingleClassWeaveGenerator extends AbstractWeaveGenerator {


    /**
     * @var ClassReflection
     */
    protected $sourceReflector;

    /**
     * @return null|MethodReflection
     */
    function addProxyMethods() {

        $sourceConstructorMethod = null;
        $methods = $this->sourceReflector->getMethods();

        foreach ($methods as $method) {
            $name = $method->getName();

            if ($name == '__construct') {
                $sourceConstructorMethod = $method;
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

        return $sourceConstructorMethod;
    }


    /**
     * Adds the properties and constants from the decorating class to the
     * class being weaved.
     * @param $originalSourceClass
     */
    function addPropertiesAndConstants($originalSourceClass) {
        $this->addPropertiesAndConstantsForReflector($this->decoratorReflector, $originalSourceClass);
        $this->addPropertiesAndConstantsForReflector($this->sourceReflector, $originalSourceClass);
    }



    /**
     * @param $originalSourceClass
     * @param $constructorParameters
     * @param $closureFactoryName
     * @param MethodReflection $sourceConstructorMethod
     * @param MethodReflection $decoratorConstructorMethod
     * @return string
     */
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




}

 
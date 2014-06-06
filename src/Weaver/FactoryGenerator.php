<?php


namespace Weaver;

use Danack\Code\Reflection\ClassReflection;
use Danack\Code\Reflection\MethodReflection;
use Danack\Code\Generator\ParameterGenerator;
use Danack\Code\Reflection\ParameterReflection;


/**
 * @param ParameterGenerator[] $parameters
 * @param bool $includeTypeHints
 * @return string
 */
function getParamsAsString($parameters, $includeTypeHints = false) {
    $string = '';
    $separator = '';

    foreach ($parameters as $parameter) {

        $string .= $separator;

        if ($includeTypeHints == true) {
            $paramGenerator = ParameterGenerator::fromReflection($parameter);
            $string .= $paramGenerator->generate();
        }
        else {
            $string .= '$'.$parameter->getName();
        }

        $separator = ', ';
    }

    return $string;
}



class FactoryGenerator {

    /**
     * @var ClassReflection
     */
    private $sourceReflection;

    /**
     * @var ClassReflection
     */
    private $decorationReflection;
    

    function __construct(ClassReflection $sourceReflection, ClassReflection $decorationReflection) {
        $this->sourceReflection = $sourceReflection;
        $this->decorationReflection = $decorationReflection;
    }


    function generate($factoryClass, $fqcn) {
        return $this->generateClosureFactoryInfo($factoryClass, $fqcn)->__toString();
    }

    /**
     * @return ClosureFactoryInfo
     */
    function generateClosureFactoryInfo($factoryClass, $fqcn) {

        $generatedClassname = getClassName($fqcn);
        $createClosureFactoryName = 'create'.$generatedClassname.'Factory';

        $decoratorParameters = [];
        $sourceParameters = [];

        if ($this->decorationReflection->hasMethod('__construct')){
            $constructorReflection = $this->decorationReflection->getMethod('__construct');
            $decoratorParameters = $constructorReflection->getParameters();
        }

        if ($this->sourceReflection->hasMethod('__construct')){
            $constructorReflection = $this->sourceReflection->getMethod('__construct');
            $sourceParameters = $constructorReflection->getParameters();
        }

        $body = $this->generateClosureFactoryBody($factoryClass, $decoratorParameters, $sourceParameters, $fqcn);
        

        $factoryInfo = new ClosureFactoryInfo(
            $createClosureFactoryName,
            $decoratorParameters,
            $body,
            $factoryClass
        );

        return $factoryInfo;
    }


    function generateClosureFactoryBody($factoryClass, $decoratorParameters, $sourceParameters, $fqcn) {

        $closureFactoryName = $factoryClass;
        
        $addedParams = $this->getAddedParameters($decoratorParameters, $sourceParameters);
        $useString = '';

        //TODO - switch this to use DanackCode
        $closureParamsString = getParamsAsString($sourceParameters, true);

        if (count($addedParams)) {
            $useString = "use (".getParamsAsString($addedParams).")";
        }

        //TODO - need to not have dupes.
        $allParams = array_merge($sourceParameters, $decoratorParameters);
        $allParamsString = getParamsAsString($allParams);
        $generatedClassname = $fqcn;

        $body = "
        \$closure = function ($closureParamsString) $useString {
            \$object = new \\$generatedClassname(
                $allParamsString
            );
    
            return \$object;
        };
    
        return new $closureFactoryName(\$closure);  
    ";

        return $body;
    }

    /**
     * @param $decoratorConstructorParameters MethodReflection[]
     * @param $sourceConstructorParameters MethodReflection[]
     * @return array
     */
    function getAddedParameters($decoratorConstructorParameters, $sourceConstructorParameters) {

        $addedParameters = array();

        foreach ($decoratorConstructorParameters as $constructorParameter) {
            $presentInOriginal = false;

            foreach ($sourceConstructorParameters as $sourceConstructorParameter) {
                if ($constructorParameter->getName() == $sourceConstructorParameter->getName()) {

                    //TODO - add type check.
                    $presentInOriginal = true;
                }
            }

            if ($presentInOriginal == false) {
                $addedParameters[] = $constructorParameter;
            }
        }

        return $addedParameters;
    }
}

 
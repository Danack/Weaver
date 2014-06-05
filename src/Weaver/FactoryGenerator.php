<?php


namespace Weaver;

use Danack\Code\Reflection\ClassReflection;
use Danack\Code\Reflection\MethodReflection;
use Danack\Code\Generator\ParameterGenerator;


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

    function generate($closureFactoryName, $fqcn) {
        return $this->generateClosureFactoryInfo($closureFactoryName, $fqcn)->__toString();
    }

    /**
     * @return ClosureFactoryInfo
     */
    function generateClosureFactoryInfo($closureFactoryName, $fqcn) {

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

        $body = $this->generateFactoryBody($decoratorParameters, $sourceParameters, $closureFactoryName, $fqcn);

        $factoryInfo = new ClosureFactoryInfo(
            $createClosureFactoryName,
            $decoratorParameters,
            $body
        );

        return $factoryInfo;
    }


    function generateFactoryBody($decoratorParameters, $sourceParameters, $closureFactoryName, $fqcn) {

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
        $generatedClassname = $fqcn;//$this->generator->getFQCN();

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



//
//
//    /**
//     * @return ClosureFactoryInfo
//     */
//    function generateClosureFactoryInfo($closureFactoryName) {
//
//        $generatedClassname = getClassName($this->getFQCN());
//        $createClosureFactoryName = 'create'.$generatedClassname.'Factory';
//
//        $decoratorParameters = [];
//        $sourceParameters = [];
//
//        if ($this->decoratorReflector->hasMethod('__construct')){
//            $constructorReflection = $this->decoratorReflector->getMethod('__construct');
//            $decoratorParameters = $constructorReflection->getParameters();
//        }
//
//        if ($this->sourceReflector->hasMethod('__construct')){
//            $constructorReflection = $this->sourceReflector->getMethod('__construct');
//            $sourceParameters = $constructorReflection->getParameters();
//        }
//
//        $body = $this->generateFactoryBody($decoratorParameters, $sourceParameters, $closureFactoryName);
//
//        $factoryInfo = new ClosureFactoryInfo(
//            $createClosureFactoryName,
//            $decoratorParameters,
//            $body
//        );
//
//        return $factoryInfo;
//    }
//
//    /**
//     * @param \ReflectionParameter[] $decoratorParameters
//     * @param \ReflectionParameter[] $sourceParameters
//     * @return string
//     */
//    function generateFactoryBody($decoratorParameters, $sourceParameters, $closureFactoryName) {
//
//        $addedParams = $this->getAddedParameters($decoratorParameters, $sourceParameters);
//        $useString = '';
//
//        if (count($addedParams)) {
//            $useString = "use(".getParamsAsString($addedParams).")";
//        }
//
//        $closureParamsString = getParamsAsString($sourceParameters, true);
//
//        //TODO - need to not have dupes.
//        $allParams = array_merge($decoratorParameters, $sourceParameters);
//        $allParamsString = getParamsAsString($allParams);
//        $sourceClassname = $this->sourceReflector->getName();
//
//
//        $body = "
//        \$closure = function ($closureParamsString) $useString {
//            \$object = new \\$sourceClassname(
//                $allParamsString
//            );
//    
//            return \$object;
//        };
//    
//        return new $closureFactoryName(\$closure);  
//    ";
//
//        return $body;
//    }
//

//
//
//    /**
//     * @param $originalSourceClass
//     * @param $decoratorConstructorParameters MethodReflection[]
//     * @return array
//     */
//    function getAddedParameters($decoratorConstructorParameters, $sourceConstructorParameters) {
//
//        $addedParameters = array();
//
//        foreach ($decoratorConstructorParameters as $constructorParameter) {
//            $presentInOriginal = false;
//
//            foreach ($sourceConstructorParameters as $sourceConstructorParameter) {
//                if ($constructorParameter->getName() == $sourceConstructorParameter->getName()) {
//
//                    //TODO - add type check.
//                    $presentInOriginal = true;
//                }
//            }
//
//            if ($presentInOriginal == false) {
//                $addedParameters[] = $constructorParameter;
//            }
//        }
//
//        return $addedParameters;
//    }
    
    
    

    
}

 
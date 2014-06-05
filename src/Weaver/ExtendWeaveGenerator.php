<?php


namespace Weaver;

use Danack\Code\Generator\ClassGenerator;
use Danack\Code\Generator\MethodGenerator;
use Danack\Code\Generator\ParameterGenerator;
use Danack\Code\Reflection\MethodReflection;
use Danack\Code\Reflection\ClassReflection;



class ExtendWeaveGenerator extends SingleClassWeaveGenerator {

    /**
     * @var MethodBinding[]
     */
    protected $methodBindingArray;


    /**
     * @var ExtendWeaveInfo
     */
    private $extendWeaveInfo;
    
    /**
     * @param $sourceClass
     * @param $decoratorClass
     * @param $methodBindingArray
     * @internal param \Weaver\MethodBinding[] $methodBinding
     */
    function __construct(ExtendWeaveInfo $extendWeaveInfo) {
        $this->extendWeaveInfo = $extendWeaveInfo;
        $this->sourceReflector = new ClassReflection($extendWeaveInfo->getSourceClass());
        $this->decoratorReflector = new ClassReflection($extendWeaveInfo->getDecoratorClass());
        $this->generator = new ClassGenerator();
        $this->generator->setName($this->getFQCN());
        $this->generator->setExtendedClass('\\'.$this->sourceReflector->getName());
    }

    /**
     * @param $savePath
     * @param $originalSourceClass
     * @param $closureFactoryName
     * @return null|string
     */
    function writeClass($outputDir, $outputClassname = null) {

        $this->addWeavedMethods();
        $this->addDecoratorMethods();
        $this->addProxyConstructor();
        $this->addPropertiesAndConstantsForReflector($this->decoratorReflector);

        $fqcn = $this->getFQCN();

        if ($outputClassname) {
            $fqcn = $outputClassname;
        }

        $this->generator->setName($fqcn);
        \Weaver\saveFile($outputDir, $fqcn, $this->generator->generate());
        
        return $fqcn;
    }


    function addWeavedMethods() {
        $methodBindingArray = $this->extendWeaveInfo->getMethodBindingArray();
        foreach ($methodBindingArray as $methodBinding) {
            $decoratorMethod = $methodBinding->getMethod();
            $decoratorMethodReflection = $this->decoratorReflector->getMethod($decoratorMethod);

            foreach ($this->sourceReflector->getMethods() as $sourceMethod) {

                if ($methodBinding->matchesMethod($sourceMethod->getName()) ) {
                    $weavedMethod = MethodGenerator::fromReflection($sourceMethod);
                    $newBody = $decoratorMethodReflection->getBody();
                    $parameters = $sourceMethod->getParameters();

                    $paramArray = [];
                    $searchArray = [];
                    
                    $count = 0;
                    foreach ($parameters as $parameter) {
                        $searchArray[] = '$param'.$count;
                        $paramArray[] = '$'.$parameter->getName();
                    }

                    $paramList = implode(', ', $paramArray);

                    $newBody = str_replace(
                        '$this->__prototype()',
                        'parent::'.$sourceMethod->getName()."($paramList)",
                        $newBody
                    );

                    $newBody = str_replace($searchArray, $paramArray, $newBody);
                    $weavedMethod->setBody($newBody);
                    $this->generator->addMethodFromGenerator($weavedMethod);
                }
            }
        }
    }


    /**
     * @param MethodReflection $sourceConstructorMethod
     * @param MethodReflection $decoratorConstructorMethod
     * @return array
     */
    function addProxyConstructor() {
        $constructorBody = '';
        $generatedParameters = array();

        if ($this->sourceReflector->hasMethod('__construct')) {
            $sourceConstructorMethod = $this->sourceReflector->getMethod('__construct');
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

        if ($this->decoratorReflector->hasMethod('__construct')) {
            $decoratorConstructorMethod = $this->decoratorReflector->getMethod('__construct');
            if ($decoratorConstructorMethod != null) {
                $parameters = $decoratorConstructorMethod->getParameters();
                foreach ($parameters as $reflectionParameter) {
                    $generatedParameters[] = ParameterGenerator::fromReflection($reflectionParameter);
                }
    
                $constructorBody .= $decoratorConstructorMethod->getBody();
            }
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

    /**
     * @return ClosureFactoryInfo
     */
    function generateClosureFactoryInfo($closureFactoryName) {

        $generatedClassname = getClassName($this->getFQCN());
        $createClosureFactoryName = 'create'.$generatedClassname.'Factory';

        $decoratorParameters = [];
        $sourceParameters = [];

        if ($this->decoratorReflector->hasMethod('__construct')){
            $constructorReflection = $this->decoratorReflector->getMethod('__construct');
            $decoratorParameters = $constructorReflection->getParameters();
        }

        if ($this->sourceReflector->hasMethod('__construct')){
            $constructorReflection = $this->sourceReflector->getMethod('__construct');
            $sourceParameters = $constructorReflection->getParameters();
        }

        $body = $this->generateFactoryBody($decoratorParameters, $sourceParameters, $closureFactoryName);

        $factoryInfo = new ClosureFactoryInfo(
            $createClosureFactoryName,
            $decoratorParameters,
            $body
        );

        return $factoryInfo;
    }


    function generateFactoryBody($decoratorParameters, $sourceParameters, $closureFactoryName) {

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
        $sourceClassname = $this->sourceReflector->getName();

        $sourceParamsString = getParamsAsString($sourceParameters);
        $generatedClassname = $this->generator->getFQCN();

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
     * @param $originalSourceClass
     * @param $decoratorConstructorParameters MethodReflection[]
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

 
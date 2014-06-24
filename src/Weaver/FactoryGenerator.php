<?php


namespace Weaver;

use Danack\Code\Generator\MethodGenerator;
use Danack\Code\Generator\PropertyGenerator;
use Danack\Code\Reflection\ClassReflection;
use Danack\Code\Reflection\MethodReflection;
use Danack\Code\Generator\ParameterGenerator;
use Danack\Code\Reflection\ParameterReflection;
use Danack\Code\Generator\ClassGenerator;

/**
 * @param ParameterGenerator[] $parameters
 * @param bool $includeTypeHints
 * @return string
 */
function getParamsAsString($parameters, $includeTypeHints = false, $addThis = false) {
    $string = '';
    $separator = '';

    $context = '';
    
    if ($addThis) {
        $context = 'this->';
    }
    
    foreach ($parameters as $parameter) {
        $string .= $separator;

        if ($includeTypeHints == true) {
            $paramGenerator = ParameterGenerator::fromReflection($parameter);
            $string .= $paramGenerator->generate();
        }
        else {
            $string .= '$'.$context.$parameter->getName();
        }

        $separator = ', ';
    }

    return $string;
}



abstract class FactoryGenerator {

    /**
     * @var ClassReflection
     */
    protected $sourceReflection;

    /**
     * @var ClassReflection
     */
    protected $decorationReflection;

    abstract function generateClosureFactoryBody($factoryClass, $decoratorParameters, $sourceParameters, $fqcn);

    /**
     * @param ClassReflection $sourceReflection
     * @param ClassReflection $decorationReflection
     */
    function __construct(ClassReflection $sourceReflection, ClassReflection $decorationReflection = null) {
        $this->sourceReflection = $sourceReflection;
        $this->decorationReflection = $decorationReflection;
    }

    function getSourceClassConstructorParams() {
        $sourceParameters = [];

        if ($this->sourceReflection->hasMethod('__construct')){
            $constructorReflection = $this->sourceReflection->getMethod('__construct');
            $sourceParameters = $constructorReflection->getParameters();
        }
        
        return $sourceParameters;
    }
    
    function getDecoratorClassConstructorParams() {
        $decoratorParameters = [];
        if ($this->decorationReflection) {
            if ($this->decorationReflection->hasMethod('__construct')) {
                $constructorReflection = $this->decorationReflection->getMethod('__construct');
                $decoratorParameters = $constructorReflection->getParameters();
            }
        }

        return $decoratorParameters;
    }
    
    
    /**
     * @param $factoryClass
     * @param $fqcn
     * @return string
     */
    function generateClosureFactoryFunction($factoryClass, $fqcn) {
        $generatedClassname = getClassName($fqcn);
        $createClosureFactoryName = 'create'.$generatedClassname.'Factory';
        $sourceParameters = $this->getSourceClassConstructorParams();
        $decoratorParameters = $this->getDecoratorClassConstructorParams();
        $body = $this->generateClosureFactoryBody($factoryClass, $decoratorParameters, $sourceParameters, $fqcn);

        $paramsString = getParamsAsString($decoratorParameters, true);

        $output = "
    /**
     * @return ".$factoryClass."
     */\n";

        $output .= "    function ".$createClosureFactoryName."($paramsString) {" ;
        $output .= $body;
        $output .= "}";
        return $output;
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

    /**
     * @var ClassGenerator
     */
    protected $generator;

    private function addConstructor() {
        
        $constructMethod = new MethodGenerator('__construct');

        
        $decoratorParameters = $this->getDecoratorClassConstructorParams();
        //$addedParams = $this->getAddedParameters($decoratorParameters, $sourceParameters);
        //$allParams = array_merge($sourceParameters, $decoratorParameters);

        $parameterGenerators = [];
        foreach ($decoratorParameters as $decoratorParameter) {
            $parameterGenerators[] = ParameterGenerator::fromReflection($decoratorParameter);
        }

        $constructMethod->setParameters($parameterGenerators);
        
        $body = '';
        
        foreach ($decoratorParameters as $decoratorParameter) {
            $body .= sprintf(
                "\$this->%s = \$%s;\n",
                lcfirst($decoratorParameter->getName()),
                lcfirst($decoratorParameter->getName())
            );
        }
        
        $constructMethod->setBody($body);
        $this->generator->addMethodFromGenerator($constructMethod);
    }

    abstract function addCreateMethod($fqcn);
    

    
    
    function generateClassFactory($factoryClassname, $fqcn) {
        $this->generator = new ClassGenerator($factoryClassname);

        $decoratorParameters = $this->getDecoratorClassConstructorParams();
        foreach ($decoratorParameters as $decoratorParameter) {
            $this->generator->addProperty(lcfirst($decoratorParameter->getName()), null, PropertyGenerator::FLAG_PROTECTED);
        }

        $this->addConstructor();
        $this->addCreateMethod($fqcn);
        return $this->generator;
    }

}

 
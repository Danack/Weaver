<?php


namespace Weaver;


use Danack\Code\Generator\ClassGenerator;
use Danack\Code\Generator\DocBlockGenerator;
use Danack\Code\Generator\MethodGenerator;
use Danack\Code\Generator\ParameterGenerator;
use Danack\Code\Reflection\MethodReflection;
use Danack\Code\Reflection\ClassReflection;
use Danack\Code\Reflection\FunctionReflection;
use Danack\Code\Generator\PropertyGenerator;

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




class ImplementsWeaveGenerator extends SingleClassWeaveGenerator  {
    
    /**
     * @param $sourceClass
     * @param $decoratorClass
     * @param $methodBindingArray MethodBinding[]
     */
    function __construct(ImplementsWeaveInfo $implementsWeaveInfo) {
        $this->implementsWeaveInfo = $implementsWeaveInfo;
        $this->sourceReflector = new ClassReflection($implementsWeaveInfo->getSourceClass());
        $this->decoratorReflector = new ClassReflection($implementsWeaveInfo->getDecoratorClass());
        $this->generator = new ClassGenerator();
        $this->methodBindingArray = $implementsWeaveInfo->getMethodBindingArray();

        $interface = $this->implementsWeaveInfo->getInterface();
        $interfaces = array($interface);
        $this->generator->setImplementedInterfaces($interfaces);
    }

    /**
     * @param $savePath
     * @param $originalSourceClass
     * @param $closureFactoryName
     * @return string
     */
    function writeClass($savePath, $outputClassname = null) {
        $this->addPropertiesAndConstantsForReflector($this->decoratorReflector);

        $lazyPropertyName = $this->implementsWeaveInfo->getLazyPropertyName();

        if ($this->generator->hasProperty($lazyPropertyName) == false) {
            $lazyProperty = new PropertyGenerator($lazyPropertyName);
            $lazyProperty->setStandardDocBlock($this->sourceReflector->getName());
            $this->generator->addPropertyFromGenerator($lazyProperty);
        }

        $factoryClassname = $this->implementsWeaveInfo->getLazyFactory();

        if ($factoryClassname) {
            $variableName = lcfirst(getClassName($factoryClassname)); 
            $newProperty = new PropertyGenerator($variableName);
            $newProperty->setStandardDocBlock($factoryClassname);
            $newProperty->setVisibility(\Danack\Code\Generator\AbstractMemberGenerator::FLAG_PRIVATE);
            $this->generator->addPropertyFromGenerator($newProperty);
        }

        $this->addProxyMethods();
        $this->addDecoratorMethods();
        $this->addInitMethod();
        $fqcn = $this->getFQCN();
        if ($outputClassname) {
            $fqcn = $outputClassname;
        }

        $this->generator->setName($fqcn);
        $text = $this->generator->generate();
        
        $text = str_replace(
            [
                ', Example\ClosureTestClassFactory',
                ', Example\TestClassFactory',
                '@var Example\TestClassFactory',
                '@var Example\TestClass',
            ], 
            [
                ', \Example\ClosureTestClassFactory',
                ', \Example\TestClassFactory',
                '@var \Example\TestClassFactory',
                '@var \Example\TestClass',
            ], 
            $text
        );

        \Weaver\saveFile($savePath, $fqcn, $text);

        return $fqcn;
    }


    

    /**
     * @param MethodReflection $sourceConstructorMethod
     * @param MethodReflection $decoratorConstructorMethod
     * @return string
     */
    function addLazyConstructor() {
        $constructorBody = '';
        $constructorParamsString = '';
        $copyBody = '';

        $generatedParameters = array();

        if ($this->sourceReflector->hasMethod('__construct')) {
            $sourceConstructorMethod = $this->sourceReflector->getMethod('__construct');
            $parameters = $sourceConstructorMethod->getParameters();
            $separator = '';

            foreach ($parameters as $reflectionParameter) {
                $generatedParameters[] = ParameterGenerator::fromReflection($reflectionParameter);
                $constructorParamsString .= $separator.'$this->'.$reflectionParameter->getName();
                $separator = ', ';
                $copyBody .= '$this->'.$reflectionParameter->getName().' = $'.$reflectionParameter->getName().";\n";
            }
        }

        if ($this->decoratorReflector->hasMethod('__construct')) {
            $decoratorConstructorMethod = $this->decoratorReflector->getMethod('__construct');
            $parameters = $decoratorConstructorMethod->getParameters();
            foreach ($parameters as $reflectionParameter) {
                $generatedParameters[] = ParameterGenerator::fromReflection($reflectionParameter);
            }

            $constructorBody .= $decoratorConstructorMethod->getBody();
        }

        $factoryInterfaceName = $this->implementsWeaveInfo->getLazyFactory();

        if ($factoryInterfaceName) {
            $variableName = lcfirst(getClassName($factoryInterfaceName));
            $constructorBody .= "    \$this->$variableName = \$$variableName;\n";
        }

        $factoryParam = $this->implementsWeaveInfo->getFactoryParameterGenerator();
        if ($factoryParam) {
            $generatedParameters[] = $factoryParam;
        }

        $constructorBody .= $copyBody;

        $this->generator->addMethod(
            '__construct',
            $generatedParameters,
            MethodGenerator::FLAG_PUBLIC,
            $constructorBody,
            ""
        );

        return $constructorParamsString;
    }


    /**
     * @param MethodReflection $sourceConstructorMethod
     * @param MethodReflection $decoratorConstructorMethod
     * @return \Danack\Code\Reflection\ParameterReflection[]
     */
    function addInitMethod() {
        $lazyPropertyName = $this->implementsWeaveInfo->getLazyPropertyName();

        $initBody = 'if ($this->'.$lazyPropertyName.' == null) {';
        $instanceFactorySignature = $this->implementsWeaveInfo->getInstanceFactorySignature();

        if ($instanceFactorySignature != null) {
            $initBody .= '
            $this->lazyInstance = '.$instanceFactorySignature.'(';
        }
        else {
            $initBody .= '
            $this->lazyInstance = new \\'.$this->sourceReflector->getName().'(';
        }

        $constructorParamsString = $this->addLazyConstructor();
        $initBody .= $constructorParamsString;
        $initBody .= ");\n}";

        $this->generator->addMethod(
            'init',
            array(),
            MethodGenerator::FLAG_PUBLIC,
            $initBody,
            ""
        );
    }

    /**
     * @param ImplementsWeaveInfo $weaveInfo
     * @param MethodReflection $method
     * @return string
     */
    function generateProxyMethodBody(MethodReflection $method) {
        $newBody = '';
        $initMethodName = $this->implementsWeaveInfo->getInitMethodName();
        $lazyPropertyName = $this->implementsWeaveInfo->getLazyPropertyName();
        
        
        $newBody .= '$this->'.$initMethodName."();\n";
        $newBody .= '$result = $this->'.$lazyPropertyName.'->'.$method->getName()."(";
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


    /**
     * @return string
     */
    function getProxiedName() {
        return $this->decoratorReflector->getShortName()."X".$this->sourceReflector->getShortName();
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

    /**
     * @param \ReflectionParameter[] $decoratorParameters
     * @param \ReflectionParameter[] $sourceParameters
     * @return string
     */
    function generateFactoryBody($decoratorParameters, $sourceParameters, $closureFactoryName) {
        
        $addedParams = $this->getAddedParameters($decoratorParameters, $sourceParameters);
        $useString = '';

        if (count($addedParams)) {
            $useString = "use(".getParamsAsString($addedParams).")";
        }

        $closureParamsString = getParamsAsString($sourceParameters, true);

        //TODO - need to not have dupes.
        $allParams = array_merge($decoratorParameters, $sourceParameters);
        $allParamsString = getParamsAsString($allParams);
        $sourceClassname = $this->sourceReflector->getName();


        $body = "
        \$closure = function ($closureParamsString) $useString {
            \$object = new \\$sourceClassname(
                $allParamsString
            );
    
            return \$object;
        };
    
        return new $closureFactoryName(\$closure);  
    ";
        
        return $body;
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

 
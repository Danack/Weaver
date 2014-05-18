<?php


namespace Weaver;


use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Reflection\MethodReflection;
use Zend\Code\Reflection\ClassReflection;


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

        $this->generator->setName($this->getFQCN());
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
    function writeClass($savePath) { //, $closureFactoryName) {
        $this->addPropertiesAndConstants();
        $this->addProxyMethods();
        $this->addDecoratorMethods();
        $this->addInitMethod();
        \Weaver\saveFile($savePath, $this->getFQCN(), $this->generator->generate());
        
        return $this->getFQCN();
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

        $factoryParam = $this->implementsWeaveInfo->getFactoryParam();
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
     * @return \Zend\Code\Reflection\ParameterReflection[]
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
     * @return null
     */
    function getInterface() {
        return $this->implementsWeaveInfo->getInterface();
    }

    /**
     * @return string
     */
    function getClosureFactoryName() {
        $originalSourceReflection = $this->sourceReflector;
        $interface = $this->implementsWeaveInfo->getInterface();
        $interfaceClassname = getClassName($interface);
        $closureFactoryName = '\\'.$originalSourceReflection->getNamespaceName().'\Closure'.$interfaceClassname.'Factory';

        return $closureFactoryName;
    }

    /**
     * @return string
     */
    function getProxiedName() {
        return $this->decoratorReflector->getShortName()."X".$this->sourceReflector->getShortName();
    }

    /**
     * @param $directory
     * @throws WeaveException
     */
    function writeFactory($directory) {
        throw new WeaveException("Not implemented.");
    }
    
}

 
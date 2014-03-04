<?php


namespace Weaver;


use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Reflection\MethodReflection;
use Zend\Code\Reflection\ClassReflection;


class ImplementsWeaveMethod extends AbstractWeaveMethod  {

    /**
     * @param $sourceClass
     * @param $decoratorClass
     * @param $methodBindingArray MethodBinding[]
     */
    function __construct(
        $sourceClass, 
        $decoratorClass, 
        ImplementsWeaveInfo $implementsWeaveInfo) {

        $this->sourceReflector = new ClassReflection($sourceClass);
        $this->decoratorReflector = new ClassReflection($decoratorClass);
        $this->generator = new ClassGenerator();
        $this->methodBindingArray = $implementsWeaveInfo->getMethodBindingArray();
        $this->implementsWeaveInfo = $implementsWeaveInfo;
        $this->setupClassName();
    }

    /**
     * @param $savePath
     * @param $originalSourceClass
     * @param $closureFactoryName
     * @return null|string
     */
    function generate($savePath, $originalSourceClass, $closureFactoryName) {
        $sourceConstructorMethod = $this->addProxyMethods();
        $decoratorConstructorMethod = $this->addDecoratorMethods();
        $constructorParameters = $this->addInitMethod($sourceConstructorMethod, $decoratorConstructorMethod);
        $this->addPropertiesAndConstants($originalSourceClass);
        $this->saveFile($savePath);
        $factoryClosure = $this->generateFactoryClosure(
                               $originalSourceClass, 
                               $constructorParameters, 
                               $closureFactoryName, 
                               $sourceConstructorMethod, 
                               $decoratorConstructorMethod);

        return $factoryClosure;
    }

    /**
     * @param MethodReflection $sourceConstructorMethod
     * @param MethodReflection $decoratorConstructorMethod
     * @return string
     */
    function addLazyConstructor(
        MethodReflection $sourceConstructorMethod = null,
        MethodReflection $decoratorConstructorMethod = null
    ) {
        $constructorBody = '';
        $constructorParamsString = '';
        $copyBody = '';

        $generatedParameters = array();

        if ($sourceConstructorMethod != null) {
            $parameters = $sourceConstructorMethod->getParameters();
            $separator = '';

            foreach ($parameters as $reflectionParameter) {
                $generatedParameters[] = ParameterGenerator::fromReflection($reflectionParameter);
                $constructorParamsString .= $separator.'$this->'.$reflectionParameter->getName();
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
    function addInitMethod(MethodReflection $sourceConstructorMethod, MethodReflection $decoratorConstructorMethod = null) {

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

        $constructorParamsString = $this->addLazyConstructor($sourceConstructorMethod,
            $decoratorConstructorMethod);

        $initBody .= $constructorParamsString;

        $initBody .= ");\n}";

        $this->generator->addMethod(
            'init',
            array(),
            MethodGenerator::FLAG_PUBLIC,
            $initBody,
            ""
        );

        return $sourceConstructorMethod->getParameters();
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
     * @var array
     */
    private $interfaces = array();
    
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

}

 
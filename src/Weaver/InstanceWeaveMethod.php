<?php


namespace Weaver;


use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Reflection\MethodReflection;
use Zend\Code\Reflection\ClassReflection;


class InstanceWeaveMethod extends AbstractWeaveMethod  {

    /**
     * @param $sourceClass
     * @param $decoratorClass
     * @param $methodBindingArray MethodBinding[]
     */
    function __construct($sourceClass, $decoratorClass, LazyWeaveInfo $lazyWeaveInfo) {

        $this->sourceReflector = new ClassReflection($sourceClass);
        $this->decoratorReflector = new ClassReflection($decoratorClass);
        $this->generator = new ClassGenerator();
        $this->methodBindingArray = $lazyWeaveInfo->getMethodBindingArray();
        $this->lazyWeaveInfo = $lazyWeaveInfo;
        $this->setupClassName();
    }
    
    function generate($savePath, $originalSourceClass) {
        $sourceConstructorMethod = $this->addProxyMethods();
        $decoratorConstructorMethod = $this->addDecoratorMethods();
        $constructorParameters = $this->addInitMethod($sourceConstructorMethod, $decoratorConstructorMethod);
        $this->addPropertiesAndConstants($originalSourceClass);
        $this->saveFile($savePath);
        $factoryClosure = $this->generateFactoryClosure($originalSourceClass, $constructorParameters, $sourceConstructorMethod, $decoratorConstructorMethod);

        return $factoryClosure;
    }

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


    function addInitMethod(MethodReflection $sourceConstructorMethod, MethodReflection $decoratorConstructorMethod = null) {

        $lazyPropertyName = $this->lazyWeaveInfo->getLazyPropertyName();

        $initBody = 'if ($this->'.$lazyPropertyName.' == null) {
            $this->lazyInstance = new \\'.$this->sourceReflector->getName().'(';

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
        
        return array();
    }

    /**
     * @param LazyWeaveInfo $weaveInfo
     * @param MethodReflection $method
     * @return string
     */
    function generateProxyMethodBody(MethodReflection $method) {
        $newBody = '';
        $initMethodName = $this->lazyWeaveInfo->getInitMethodName();
        $lazyPropertyName = $this->lazyWeaveInfo->getLazyPropertyName();
        $newBody .= '$this->'.$initMethodName."();\n";
        $newBody .= '$result = $this->'.$lazyPropertyName.'->'.$method->getName()."(";
        $parameters = $method->getParameters();
        $separator = '';

        foreach ($parameters as $reflectionParameter) {
            $newBody .= $separator.'$'.$reflectionParameter->getName();
            $separator = ', ';
        }

        $newBody .= ");\n";

        // if contains @return $method->getDocBlock(); ? nah thats dumb
//        if ($methodBinding->getHasResult()) {
//            $newBody .= 'return $result;'."\n";
//        }

        return $newBody;
    }


    /**
     * @var array
     */
    private $interfaces = array();
    
    function getInterface() {
        return $this->lazyWeaveInfo->getInterface();
    }



    function getClosureFactoryName() {
        //$originalSourceReflection = new ClassReflection($originalSourceClass);
        $originalSourceReflection = $this->sourceReflector;
        $interface = $this->lazyWeaveInfo->getInterface();
        $itnerfaceClassname = getClassName($interface);
        $closureFactoryName = '\\'.$originalSourceReflection->getNamespaceName().'\Closure'.$itnerfaceClassname.'Factory';

        return $closureFactoryName;
    }

}

 
<?php


namespace Weaver;


use Danack\Code\Generator\ClassGenerator;
use Danack\Code\Generator\MethodGenerator;
use Danack\Code\Generator\ParameterGenerator;
use Danack\Code\Reflection\MethodReflection;
use Danack\Code\Reflection\ClassReflection;
use Danack\Code\Generator\PropertyGenerator;


class LazyWeaveGenerator extends SingleClassWeaveGenerator  {

    use \Intahwebz\SafeAccess;

    private $lazyWeaveInfo;

    /**
     * @param $sourceClass
     * @param LazyWeaveInfo $lazyWeaveInfo
     */
    function __construct($sourceClass, LazyWeaveInfo $lazyWeaveInfo) {
        //TODO - check sourceClass implements alleged interface(s)
        //TODO - check sourceClass has properties that match the constructors parameters
        $this->lazyWeaveInfo = $lazyWeaveInfo;
        $this->sourceReflection = new ClassReflection($sourceClass);
        $this->decoratorReflection = new ClassReflection($lazyWeaveInfo->getDecoratorClass());
        $this->generator = new ClassGenerator();
        //$this->methodBindingArray = $implementsWeaveInfo->getMethodBindingArray();

        $interface = $this->lazyWeaveInfo->getInterface();
        $interfaces = array($interface);
        $this->generator->setImplementedInterfaces($interfaces);
    }

    /**
     * @return WeaveResult
     */
    function generate() {
        $this->addPropertiesAndConstantsFromReflection($this->decoratorReflection);

        $lazyPropertyName = $this->lazyWeaveInfo->getLazyPropertyName();

        if ($this->generator->hasProperty($lazyPropertyName) == false) {
            $lazyProperty = new PropertyGenerator($lazyPropertyName);
            $lazyProperty->setStandardDocBlock($this->sourceReflection->getName());
            $this->generator->addPropertyFromGenerator($lazyProperty);
        }

        $this->addPropertiesFromConstructor();
        $this->addProxyMethods();
        $this->addDecoratorMethods();
        $this->addInitMethod();
        $fqcn = $this->getFQCN();
        $this->generator->setName($fqcn);
        $factoryGenerator = new FactoryGenerator(
                                $this->sourceReflection,
                                $this->decoratorReflection
                            );

        return new WeaveResult($this->generator, $factoryGenerator);
    }


    function addPropertiesFromConstructor() {
        if ($this->sourceReflection->hasMethod('__construct')) {
            $sourceConstructorMethod = $this->sourceReflection->getMethod('__construct');
            $parameters = $sourceConstructorMethod->getParameters();
        
            foreach ($parameters as $reflectionParameter) {
                $propertyGenerator = new PropertyGenerator($reflectionParameter->getName());                
                $propertyGenerator->setStandardDocBlock($reflectionParameter->getType());
                $this->generator->addPropertyFromGenerator($propertyGenerator);
            }
        }
    }

    /**
     * @internal param MethodReflection $sourceConstructorMethod
     * @internal param MethodReflection $decoratorConstructorMethod
     * @return string
     */
    function addLazyConstructor() {
        $constructorBody = '';
        $constructorParamsString = '';
        $copyBody = '';

        $generatedParameters = array();

        if ($this->sourceReflection->hasMethod('__construct')) {
            $sourceConstructorMethod = $this->sourceReflection->getMethod('__construct');
            $parameters = $sourceConstructorMethod->getParameters();
            $separator = '';

            foreach ($parameters as $reflectionParameter) {
                $generatedParameters[] = ParameterGenerator::fromReflection($reflectionParameter);
                $constructorParamsString .= $separator.'$this->'.$reflectionParameter->getName();
                $separator = ', ';
                $copyBody .= '$this->'.$reflectionParameter->getName().' = $'.$reflectionParameter->getName().";\n";
            }
        }

        if ($this->decoratorReflection->hasMethod('__construct')) {
            $decoratorConstructorMethod = $this->decoratorReflection->getMethod('__construct');
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


    /**
     * 
     */
    function addInitMethod() {
        $lazyPropertyName = $this->lazyWeaveInfo->getLazyPropertyName();

        
        $initBody = 'if ($this->'.$lazyPropertyName." == null) {\n";

        $initBody .= sprintf(
            '$this->%s = new \\%s(',
            $this->lazyWeaveInfo->getLazyPropertyName(),
            $this->sourceReflection->getName()
        );
        
        
        //TODO - ^^ bug right there. lazyInstance isn't always the name.
        
        $constructorParamsString = $this->addLazyConstructor();
        $initBody .= $constructorParamsString;
        $initBody .= ");\n}";

        
        
        $this->generator->addMethod(
            $this->lazyWeaveInfo->getInitMethodName(),
            array(),
            MethodGenerator::FLAG_PUBLIC,
            $initBody,
            ""
        );
    }

    /**
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
        $newBody .= 'return $result;'."\n";

        return $newBody;
    }


    /**
     * Generates a name that indicates what the class is composed of.
     * e.g. A DB class decorated with Timer would be TimerXDB
     * 
     * @return string
     */
    function generateWeavedName() {
        return $this->decoratorReflection->getShortName()."X".$this->sourceReflection->getShortName();
    }


    /**
     * 
     */
    function addProxyMethods() {
        $methods = $this->sourceReflection->getMethods();

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
}

 
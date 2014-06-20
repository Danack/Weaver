<?php


namespace Weaver;

use Danack\Code\Generator\ClassGenerator;
use Danack\Code\Generator\MethodGenerator;
use Danack\Code\Generator\ParameterGenerator;
use Danack\Code\Reflection\MethodReflection;
use Danack\Code\Reflection\ClassReflection;

class ImplementWeaveGenerator extends SingleClassWeaveGenerator {

    use \Intahwebz\SafeAccess;

    /**
     * @var ExtendWeaveInfo|ImplementWeaveInfo
     */
    private $implementWeaveInfo;
    
    /**
     * @param $sourceClass
     * @param ImplementWeaveInfo $implementWeaveInfo
     * @internal param $decoratorClass
     * @internal param $methodBindingArray
     * @internal param \Weaver\MethodBinding[] $methodBinding
     */
    function __construct($sourceClass, ImplementWeaveInfo $implementWeaveInfo) {
        $this->implementWeaveInfo = $implementWeaveInfo;
        $this->sourceReflection = new ClassReflection($sourceClass);
        $this->decoratorReflection = new ClassReflection($implementWeaveInfo->getDecoratorClass());
        $this->generator = new ClassGenerator();
        $this->generator->setName($this->getFQCN());
        $interface = $this->implementWeaveInfo->getInterface();


        $interfaceToImplement = $implementWeaveInfo->getInterface();

        $wat = $this->sourceReflection->implementsInterface($interfaceToImplement);
        //$wat = $this->sourceReflection->implementsInterface("badsadbad");
        echo $wat;
        
        if (!$wat) {
            throw new WeaveException("Class $sourceClass does not implement interface $interfaceToImplement, weaving is not possible.");
        }

        $interfaces = array($interface);
        $this->generator->setImplementedInterfaces($interfaces);
    }

    /**
     * @return WeaveResult
     */
    function generate() {
        $this->addDecoratedMethods();
        $this->addDecoratorMethods();
        $this->addProxyConstructor();
        $this->addPropertiesAndConstantsFromReflection($this->decoratorReflection);
        $fqcn = $this->getFQCN();
        $this->generator->setName($fqcn);
        $factoryGenerator = new FactoryGenerator($this->sourceReflection, $this->decoratorReflection, null);

        return new WeaveResult($this->generator, $factoryGenerator);
    }

    /**
     * Decorate the method and call the instance.
     * @param MethodReflection $sourceMethod
     * @param MethodReflection $decoratorMethodReflection
     */
    function addDecoratedMethod(MethodReflection $sourceMethod, MethodReflection $decoratorMethodReflection ) {
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


    /**
     * Add a call to the instance method.
     * @param MethodReflection $sourceMethod
     */
    function addPlainMethod(MethodReflection $sourceMethod) {

        $parameters = $sourceMethod->getParameters();
        $paramArray = [];
        foreach ($parameters as $parameter) {
            $paramArray[] = '$'.$parameter->getName();
        }

        $paramList = implode(', ', $paramArray);

        $weavedMethod = MethodGenerator::fromReflection($sourceMethod);
        
        $body = sprintf(
            "    \$this->instance->%s(%s);",
            $sourceMethod->getName(),
            $paramList
        );
        
        $weavedMethod->setBody($body);
        $this->generator->addMethodFromGenerator($weavedMethod);
    }
    
    /**
     * For all of the methods that need to be decorated, generate the decorated version
     * and all the to the generator.
     */
    function addDecoratedMethods() {
        $methodBindingArray = $this->implementWeaveInfo->getMethodBindingArray();
        foreach ($methodBindingArray as $methodBinding) {
            $decoratorMethod = $methodBinding->getMethod();
            $decoratorMethodReflection = $this->decoratorReflection->getMethod($decoratorMethod);

            foreach ($this->sourceReflection->getMethods() as $sourceMethod) {
                if ($methodBinding->matchesMethod($sourceMethod->getName()) ) {
                    $this->addDecoratedMethod($sourceMethod, $decoratorMethodReflection);
                }
                else {
                    $this->addPlainMethod($sourceMethod);
                }
            }
        }
    }


    /**
     * @internal param MethodReflection $sourceConstructorMethod
     * @internal param MethodReflection $decoratorConstructorMethod
     * @return array
     */
    function addProxyConstructor() {
        $constructorBody = '';
        $generatedParameters = array();

        $paramName = lcfirst($this->sourceReflection->getShortName()); 
        
        $generatedParameters[] = new ParameterGenerator(
            $paramName,
            $this->sourceReflection->getName()
        );

        $constructorBody .= sprintf(
            "    \$this->%s = \$%s;\n",
            $this->implementWeaveInfo->getInstancePropertyName(),
            $paramName
        );

        if ($this->decoratorReflection->hasMethod('__construct')) {
            $decoratorConstructorMethod = $this->decoratorReflection->getMethod('__construct');
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
}

 
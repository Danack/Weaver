<?php


namespace Weaver;

use Danack\Code\Generator\ClassGenerator;
use Danack\Code\Generator\MethodGenerator;
use Danack\Code\Generator\ParameterGenerator;
use Danack\Code\Generator\PropertyGenerator;
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

        

        $interfaceToImplement = $implementWeaveInfo->getInterface();

        if (!$this->sourceReflection->implementsInterface($interfaceToImplement)) {
            throw new WeaveException("Class $sourceClass does not implement interface $interfaceToImplement, weaving is not possible.", WeaveException::INTERFACE_NOT_IMPLEMENTED);
        }

        $interfaces = array($interfaceToImplement);
        $this->generator->setImplementedInterfaces($interfaces);
    }

    function addInstanceProperty() {
        $newProperty = new PropertyGenerator($this->implementWeaveInfo->getInstancePropertyName());
        $newProperty->setVisibility(\Danack\Code\Generator\AbstractMemberGenerator::FLAG_PRIVATE);
        $this->generator->addPropertyFromGenerator($newProperty);
    }
    
    /**
     * @return WeaveResult
     */
    function generate() {
        $this->addInstanceProperty();
        $this->addSourceMethods();
        $this->addProxyConstructor();
        $this->addDecoratorMethods();
        $this->addPropertiesAndConstantsFromReflection($this->decoratorReflection);
        $fqcn = $this->getFQCN();
        $this->generator->setName($fqcn);
        $factoryGenerator = new ImplementFactoryGenerator($this->sourceReflection, $this->decoratorReflection, null);

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
            '    $this->weavedInstance->'.$sourceMethod->getName()."($paramList)",
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
            " return \$this->%s->%s(%s);",
            $this->implementWeaveInfo->getInstancePropertyName(),
            $sourceMethod->getName(),
            $paramList
        );
        
        $weavedMethod->setBody($body);
        $this->generator->addMethodFromGenerator($weavedMethod);
    }
    
    /**
     * For all of the methods  in that need to be decorated, generate the decorated version
     * and all the to the generator.
     * @TODO Shouldn't this only implement the methods in the interface that is being exposed?
     */
    function addSourceMethods() {

        $methodBindingArray = $this->implementWeaveInfo->getMethodBindingArray();
        
        $methods = $this->sourceReflection->getMethods();

        foreach ($methods as $sourceMethod) {
            
            if ($sourceMethod->getName() === '__construct') {
                continue;
            }
            
            $methodBindingToApply = null;
            foreach ($methodBindingArray as $methodBinding) {
                if ($methodBinding->matchesMethod($sourceMethod->getName()) ) {
                    $methodBindingToApply = $methodBinding;
                    break;
                }
            }

            if ($methodBindingToApply != null) {
                $decoratorMethod = $methodBindingToApply->getMethod();
                $decoratorMethodReflection = $this->decoratorReflection->getMethod($decoratorMethod);
                $this->addDecoratedMethod($sourceMethod, $decoratorMethodReflection);
            }
            else {
                $this->addPlainMethod($sourceMethod);
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
            "\$this->%s = \$%s;\n",
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

 
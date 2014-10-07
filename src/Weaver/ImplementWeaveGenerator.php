<?php


namespace Weaver;

use Danack\Code\Generator\ClassGenerator;
use Danack\Code\Generator\MethodGenerator;
use Danack\Code\Generator\ParameterGenerator;
use Danack\Code\Generator\PropertyGenerator;
use Danack\Code\Reflection\MethodReflection;
use Danack\Code\Reflection\ClassReflection;

/**
 * Trims the whitespace at the start of each line, by the amount of whitespace
 * in the first line that contains code. 
 * 
 * @param $body
 * @return string
 */
function trimBody($body) {

    $newBody = '';    
    $lines = explode("\n", $body);
    $whitespaceToRemove = null;

    foreach($lines as $line) {

        if ($whitespaceToRemove === null) {
            $matchCount = preg_match ('#\S+#', $line, $matches, PREG_OFFSET_CAPTURE);
            if ($matchCount) {
                $position = $matches[0][1];
                $whitespaceToRemove = substr($line, 0, $position);
            }
        }

        if ($whitespaceToRemove !== null && strpos($line, $whitespaceToRemove) === 0) {
            $newBody .= substr($line, strlen($whitespaceToRemove)); 
        }
        else {
            $newBody .= $line;
        }

        $newBody .= "\n";
    }

    return $newBody;
}

class ImplementWeaveGenerator extends SingleClassWeaveGenerator {

    use \Intahwebz\SafeAccess;

    /**
     * @var ImplementWeaveInfo
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
        $this->sourceClassReflection = new ClassReflection($sourceClass);
        $this->decoratorReflection = new ClassReflection($implementWeaveInfo->getDecoratorClass());
        $this->generator = new ClassGenerator();
        $this->generator->setName($this->getFQCN());

        $interfaceToImplement = $implementWeaveInfo->getInterface();

        if (!$this->sourceClassReflection->implementsInterface($interfaceToImplement)) {
            throw new WeaveException("Class $sourceClass does not implement interface $interfaceToImplement, weaving is not possible.", WeaveException::INTERFACE_NOT_IMPLEMENTED);
        }

        $interfaces = array($interfaceToImplement);
        $this->generator->setImplementedInterfaces($interfaces);
    }

    /**
     * 
     */
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
        $this->addDecoratorConstructor();
        $this->addDecoratorMethods();
        $this->addPropertiesAndConstantsFromReflection($this->decoratorReflection);
        $fqcn = $this->getFQCN();
        $this->generator->setName($fqcn);
        $factoryGenerator = new ImplementFactoryGenerator($this->sourceClassReflection, $this->decoratorReflection, null);

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

        $newBody = trimBody($newBody);

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
            '$this->weavedInstance->'.$sourceMethod->getName()."($paramList)",
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
        $methods = $this->sourceClassReflection->getMethods();
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
    function addDecoratorConstructor() {
        $constructorBody = '';
        $generatedParameters = array();

        $paramName = lcfirst($this->sourceClassReflection->getShortName()); 
        
        $generatedParameters[] = new ParameterGenerator(
            $paramName,
            $this->sourceClassReflection->getName()
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

 
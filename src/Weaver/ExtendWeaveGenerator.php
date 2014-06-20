<?php


namespace Weaver;

use Danack\Code\Generator\ClassGenerator;
use Danack\Code\Generator\MethodGenerator;
use Danack\Code\Generator\ParameterGenerator;
use Danack\Code\Reflection\MethodReflection;
use Danack\Code\Reflection\ClassReflection;

class ExtendWeaveGenerator extends SingleClassWeaveGenerator {

    use \Intahwebz\SafeAccess;

    /**
     * @var ExtendWeaveInfo
     */
    private $extendWeaveInfo;

    /**
     * @param $sourceClass
     * @param ExtendWeaveInfo $extendWeaveInfo
     * @internal param $decoratorClass
     * @internal param $methodBindingArray
     * @internal param \Weaver\MethodBinding[] $methodBinding
     */
    function __construct($sourceClass, ExtendWeaveInfo $extendWeaveInfo) {
        $this->extendWeaveInfo = $extendWeaveInfo;
        $this->sourceReflection = new ClassReflection($sourceClass);
        $this->decoratorReflection = new ClassReflection($extendWeaveInfo->getDecoratorClass());
        $this->generator = new ClassGenerator();
        $this->generator->setName($this->getFQCN());
        $this->generator->setExtendedClass('\\'.$this->sourceReflection->getName());
    }

    /**
     * @return WeaveResult
     */
    function generate() {
        $this->addWeavedMethods();
        $this->addDecoratorMethods();
        $this->addProxyConstructor();
        $this->addPropertiesAndConstantsFromReflection($this->decoratorReflection);
        $fqcn = $this->getFQCN();
        $this->generator->setName($fqcn);
        $factoryGenerator = new FactoryGenerator($this->sourceReflection, $this->decoratorReflection, null);

        return new WeaveResult($this->generator, $factoryGenerator);
    }

    /**
     * 
     */
    function addWeavedMethods() {
        $methodBindingArray = $this->extendWeaveInfo->getMethodBindingArray();
        foreach ($methodBindingArray as $methodBinding) {
            $decoratorMethod = $methodBinding->getMethod();
            $decoratorMethodReflection = $this->decoratorReflection->getMethod($decoratorMethod);

            foreach ($this->sourceReflection->getMethods() as $sourceMethod) {

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
     * @internal param MethodReflection $sourceConstructorMethod
     * @internal param MethodReflection $decoratorConstructorMethod
     * @return array
     */
    function addProxyConstructor() {
        $constructorBody = '';
        $generatedParameters = array();

        if ($this->sourceReflection->hasMethod('__construct')) {
            $sourceConstructorMethod = $this->sourceReflection->getMethod('__construct');
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

 
<?php


namespace Weaver;

use Zend\Code\Generator\ClassGenerator;

use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Reflection\MethodReflection;
use Zend\Code\Reflection\ClassReflection;

class ExtendWeaveGenerator extends SingleClassWeaveGenerator {


    /**
     * @param $sourceClass
     * @param $decoratorClass
     * @param $methodBindingArray
     * @internal param \Weaver\MethodBinding[] $methodBinding
     */
    function __construct($sourceClass, $decoratorClass, $methodBindingArray) {
        $this->sourceReflector = new ClassReflection($sourceClass);
        $this->decoratorReflector = new ClassReflection($decoratorClass);
        $this->generator = new ClassGenerator();
        $this->methodBindingArray = $methodBindingArray;
        $this->setupClassName();
    }

    /**
     * @return null
     */
    function getInterface() {
        return null;
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
        $constructorParameters = $this->addProxyConstructor($sourceConstructorMethod, $decoratorConstructorMethod);
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
     * @return array
     */
    function addProxyConstructor(
        MethodReflection $sourceConstructorMethod = null,
        MethodReflection $decoratorConstructorMethod = null
    ) {

        $constructorBody = '';

        $generatedParameters = array();

        if ($sourceConstructorMethod != null) {
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

        if ($decoratorConstructorMethod != null) {
            $parameters = $decoratorConstructorMethod->getParameters();
            foreach ($parameters as $reflectionParameter) {
                $generatedParameters[] = ParameterGenerator::fromReflection($reflectionParameter);
            }

            $constructorBody .= $decoratorConstructorMethod->getBody();
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


    /**
     * @param MethodReflection $method
     * @return bool|string
     */
    function generateProxyMethodBody(MethodReflection $method) {
        $name = $method->getName();

        $methodBinding = $this->getMethodBindingForMethod($name);

        if (!$methodBinding) {
            return false;
        }

        $newBody = '';
        
        $beforeFunction = $methodBinding->getBefore();
        
        if ($beforeFunction) {
            $newBody .= $beforeFunction."\n";
        }

        if ($methodBinding->getHasResult()) {
            $newBody .= '$result = parent::'.$method->getName()."(";
        }
        else {
            $newBody .= 'parent::'.$method->getName()."(";
        }
        $parameters = $method->getParameters();
        $separator = '';

        foreach ($parameters as $reflectionParameter) {
            $newBody .= $separator.'$'.$reflectionParameter->getName();
            $separator = ', ';
        }

        $newBody .= ");\n";

        $afterFunction = $methodBinding->getAfter();

        if ($afterFunction) {
            $newBody .= $afterFunction."\n\n";
        }

        if ($methodBinding->getHasResult()) {
            $newBody .= 'return $result;'."\n";
        }

        return $newBody;
    }

    /**
     * @return string
     */
    function getClosureFactoryName() {
        $originalSourceReflection = $this->sourceReflector;
        $closureFactoryName = '\\'.$originalSourceReflection->getNamespaceName().'\Closure'.$originalSourceReflection->getShortName().'Factory';

        return $closureFactoryName;
    }


    //TODO - move to singleSourceClassGenerator
    function getNamespaceName() {
        return $this->sourceReflector->getNamespaceName();
    }

    //TODO - move to singleSourceClassGenerator
    function getProxiedName() {
        return $this->decoratorReflector->getShortName()."X".$this->sourceReflector->getShortName();
    }

    /**
     *
     */
    function setupClassName() {
        $this->generator->setName($this->getFQCN());
        $this->generator->setExtendedClass('\\'.$this->sourceReflector->getName());
    }



}

 
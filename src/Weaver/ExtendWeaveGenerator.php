<?php


namespace Weaver;

use Zend\Code\Generator\ClassGenerator;

use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Reflection\MethodReflection;
use Zend\Code\Reflection\ClassReflection;

class ExtendWeaveGenerator extends SingleClassWeaveGenerator {

    /**
     * @var MethodBinding[]
     */
    protected $methodBindingArray;
    
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
        $this->generator->setName($this->getFQCN());
        $this->generator->setExtendedClass('\\'.$this->sourceReflector->getName());

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
    function writeClass($outputDir) {
        $this->addProxyMethods();
        $this->addDecoratorMethods();
        $this->addProxyConstructor();
        $this->addPropertiesAndConstants();
        $this->generator->setName($this->getFQCN());
        \Weaver\saveFile($outputDir, $this->getFQCN(), $this->generator->generate());
    }


    /**
     * @param $name
     * @return null|MethodBinding
     */
    function getMethodBindingForMethod($name) {
        foreach ($this->methodBindingArray as $methodBinding) {
            if ($methodBinding->matchesMethod($name) == true) {
                return $methodBinding;
            }
        }

        return null;
    }

    /**
     * @param MethodReflection $sourceConstructorMethod
     * @param MethodReflection $decoratorConstructorMethod
     * @return array
     */
    function addProxyConstructor() {
        $constructorBody = '';
        $generatedParameters = array();

        $sourceConstructorMethod = $this->sourceReflector->getConstructor();
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

        $decoratorConstructorMethod = $this->decoratorReflector->getConstructor();
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

    /**
     * @param $directory
     * @throws \Exception
     */
    function writeFactory($directory) {
        throw new \Exception("writefactory not implemented.");
    }
}

 
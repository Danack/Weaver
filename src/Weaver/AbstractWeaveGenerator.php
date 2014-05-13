<?php


namespace Weaver;


use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Reflection\MethodReflection;
use Zend\Code\Reflection\ClassReflection;


function getConstructorParamsString($constructorParameters, $includeTypeHints = false) {
    $string = '';
    $separator = '';

    foreach ($constructorParameters as $constructorParameter) {
        $string .= $separator;

        /** @var $constructorParameter ParameterGenerator */
        if ($includeTypeHints) {
            $typeHint = $constructorParameter->getType();
            if ($typeHint) {
                $string .= $typeHint.' ';
            }
        }

        $string .= '$'.$constructorParameter->getName();
        $separator = ', ';
    }

    return $string;
}


abstract class AbstractWeaveGenerator {

   

    /**
     * @var ClassReflection
     */
    protected $decoratorClassReflector;

    /**
     * @var \Zend\Code\Generator\ClassGenerator
     */
    protected $generator;

//    /**
//     * @var MethodBinding[]
//     */
//    protected $methodBindingArray;

    /**
     * @var string
     */
    private $fqcn = null;

    /**
     * @return string
     */
    function getFQCN() {
        if ($this->fqcn == null) {

            $namespace = $this->getNamespaceName();
            $classname = $this->getProxiedName();

            if (strlen($namespace)) {
                $this->fqcn = $namespace.'\\'.$classname;
            }
            else {
                $this->fqcn = $classname;
            }
        }

        return $this->fqcn;
    }

    /**
     * @param ClassReflection $reflector
     * @param $originalSourceClass
     */
    function addPropertiesAndConstantsForReflector(ClassReflection $reflector, $originalSourceClass) {
        $constants = $reflector->getConstants();

        foreach ($constants as $name => $value) {
            $this->generator->addProperty($name, $value, PropertyGenerator::FLAG_CONSTANT);
        }

        $properties = $reflector->getProperties();

        foreach ($properties as $property) {
            $newProperty = PropertyGenerator::fromReflection($property);
            $newProperty->setDocBlock(" @var \\$originalSourceClass");
            $this->generator->addPropertyFromGenerator($newProperty);
        }
    }
        
    

    /**
     * @return null|MethodReflection
     */
    function addDecoratorMethods() {

        $decoratorConstructorMethod = null;

        $methods = $this->decoratorReflector->getMethods();

        foreach ($methods as $method) {
            $name = $method->getName();

            if ($name == '__construct') {
                $decoratorConstructorMethod = $method;
                continue;
            }

            $parameters = $method->getParameters();

            $generatedParameters = array();

            foreach ($parameters as $reflectionParameter) {
                $generatedParameters[] = ParameterGenerator::fromReflection($reflectionParameter);
            }

            $this->generator->addMethod(
                $name,
                $generatedParameters,
                MethodGenerator::FLAG_PUBLIC,
                $method->getBody(),
                $method->getDocBlock()
            );
        }

        return $decoratorConstructorMethod;
    }

    /**
     * @param $originalSourceClass
     * @param $constructorParameters MethodReflection[]
     * @return array
     */
    function getAddedParameters($originalSourceClass, $constructorParameters) {

        $originalSourceReflection = new ClassReflection($originalSourceClass);

        $sourceConstructorParameters = array();
        
        $constructor = $originalSourceReflection->getConstructor();
        
        if ($constructor) {
            $sourceConstructorParameters = $constructor->getParameters();
        }

        $addedParameters = array();
        
        if (is_array($constructorParameters) == false) {
            throw new \ErrorException("Constructor params needs to be an array, for some reason it isn't.");
        }

        foreach ($constructorParameters as $constructorParameter) {
            $presentInOriginal = false;
            
            foreach ($sourceConstructorParameters as $sourceConstructorParameter) {
                if ($constructorParameter->getName() == $sourceConstructorParameter->getName()) {
                    $presentInOriginal = true;
                }
            }
            
            if ($presentInOriginal == false) {
                $addedParameters[] = $constructorParameter;
            }
        }

        return $addedParameters;
    }

    /**
     * @param $savePath
     * @throws \RuntimeException
     */
    protected function saveFile($savePath, $text) {

        mkdir($savePath, true);
        $filename = str_replace('\\', '/', $this->getFQCN());

        $fileHeader = <<< END
<?php

//Auto-generated by Weaver - https://github.com/Danack/Weaver
//
//Do not be surprised if any changes to this file are over-written.

END;

        $text = $this->applyHacks($text);
        $written = file_put_contents($savePath.'/'.$filename, $fileHeader.$text);

        if ($written == false) {
            throw new \RuntimeException("Failed to write file $filename.");
        }
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
     * @param $savePath
     * @param $originalSourceClass
     * @return string|null
     */
    abstract function generate($savePath, $originalSourceClass, $closureFactoryName);

    /**
     * @param MethodReflection $methodReflection
     * @return mixed
     */
    abstract function generateProxyMethodBody(MethodReflection $methodReflection);

    /**
     * @return mixed
     */
    abstract function getInterface();

    /**
     * Get the name of the generated factory class that creates this weaved class.
     * @return mixed
     */
    abstract function getClosureFactoryName();

    abstract function getNamespaceName();

    abstract function getProxiedName();

    abstract function setupClassName();

    abstract function addProxyMethods();

    abstract function addPropertiesAndConstants($originalSourceClass);

    function applyHacks($sourceCode) {
        return $sourceCode;
    }

    abstract function generateFactoryClosure();
}

 
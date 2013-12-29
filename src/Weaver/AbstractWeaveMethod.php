<?php


namespace Weaver;

use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

use Zend\Code\Reflection\MethodReflection;

use Zend\Code\Reflection\ClassReflection;


//$decoratorUseParams = getUseParams($addedParameters);

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


abstract class AbstractWeaveMethod {

    /**
     * @var ClassReflection
     */
    protected $sourceReflector;

    /**
     * @var ClassReflection
     */
    protected $decoratorReflector;

    /**
     * @var ClassGenerator
     */
    protected $generator;

    /**
     * @var MethodBinding[]
     */
    protected $methodBindingArray;

    /**
     * @var string
     */
    private $fqcn = null;

    /**
     * @var array
     */
    private $interfaces = array();

    function getFQCN() {
        if ($this->fqcn == null) {
            $namespace = $this->sourceReflector->getNamespaceName();
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

    function getClosureFactoryName($originalSourceClass) {
        $originalSourceReflection = new ClassReflection($originalSourceClass);
        $closureFactoryName = '\\'.$originalSourceReflection->getNamespaceName().'\Closure'.$originalSourceReflection->getShortName().'Factory';

        return $closureFactoryName;
    }
    
    function getInterfaces() {
        return $this->interfaces;
    }

    function setupClassName() {
        $this->generator->setName($this->getFQCN());

        $interfaces = $this->getInterfaces();
        
        if ($interfaces) {
            $this->generator->setImplementedInterfaces($interfaces);
        }
        else {
            $this->generator->setExtendedClass('\\'.$this->sourceReflector->getName());
        }
    }

    function getProxiedName() {
        return $this->decoratorReflector->getShortName()."X".$this->sourceReflector->getShortName();
    }

    function addPropertiesAndConstants() {
        $constants = $this->decoratorReflector->getConstants();

        foreach ($constants as $name => $value) {
            $this->generator->addProperty($name, $value, PropertyGenerator::FLAG_CONSTANT);
        }

        $properties = $this->decoratorReflector->getProperties();

        foreach ($properties as $property) {
            $newProperty = PropertyGenerator::fromReflection($property);
            $this->generator->addPropertyFromGenerator($newProperty);
        }
    }

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


    function generateFactoryClosure(
        $originalSourceClass,
        $constructorParameters,
        MethodReflection $sourceConstructorMethod,
        MethodReflection $decoratorConstructorMethod
    ) {

        $fqcn = $this->getFQCN();

        if ($decoratorConstructorMethod != null) {
            $parameters = $decoratorConstructorMethod->getParameters();
            foreach ($parameters as $reflectionParameter) {
                $generatedParameters[] = ParameterGenerator::fromReflection($reflectionParameter);
            }
        }

        $className = '\\'.$fqcn;

        $addedParameters = $this->getAddedParameters($originalSourceClass, $constructorParameters);

        $originalSourceReflection = new ClassReflection($originalSourceClass);
        $originalConstructorParameters = array();
        $originalConstructor = $originalSourceReflection->getConstructor();
        
        if ($originalConstructor) {
            $originalConstructorParameters = $originalConstructor->getParameters();
        }

        $decoratorParamsWithType = getConstructorParamsString($addedParameters, true);   
        $decoratorUseParams = getConstructorParamsString($addedParameters);
        $objectParams = getConstructorParamsString($originalConstructorParameters);
        $allParams = getConstructorParamsString($constructorParameters);

        $closureFactoryName = $this->getClosureFactoryName($originalSourceClass);        
        $createClosureFactoryName = 'create'.$this->getProxiedName().'Factory';

        $function = <<< END
function $createClosureFactoryName($decoratorParamsWithType) {

    \$closure = function ($objectParams)
        use ($decoratorUseParams)
    {
        \$object = new $className(
            $allParams
        );

        return \$object;
    };

    return new $closureFactoryName(\$closure);
}

END;
        
        
        return $function;
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




    function saveFile($savePath) {

        $fqcn = $this->getFQCN();
        $filename = $savePath.'/'.$fqcn.'.php';
        $filename = str_replace('\\', '/', $filename);
        ensureDirectoryExists($filename);

        $fileHeader = <<< END
<?php

//Auto-generated by Weaver - https://github.com/Danack/Weaver
//
//Do not be surprised if any changes to this file are over-written.


END;

        $written = file_put_contents($filename, $fileHeader.$this->generator->generate());

        if ($written == false) {
            throw new \RuntimeException("Failed to write file $filename.");
        }
    }



    function addProxyMethods() {

        $sourceConstructorMethod = null;

        $methods = $this->sourceReflector->getMethods();

        foreach ($methods as $method) {

            $name = $method->getName();

            if ($name == '__construct') {
                $sourceConstructorMethod = $method;
                continue;
            }

            $parameters = $method->getParameters();
            $docBlock = $method->getDocBlock();

            if ($docBlock) {
                $docBlock = DocBlockGenerator::fromReflection($docBlock);
            }

            $generatedParameters = array();

            foreach ($parameters as $reflectionParameter) {
                $generatedParameters[] = ParameterGenerator::fromReflection($reflectionParameter);
            }

            $newBody = $this->generateProxyMethodBody($method, $this->methodBindingArray);

            //var_dump($this->weaving);
            //echo($newBody);

            if ($newBody) {
                $this->generator->addMethod(
                    $name,
                    $generatedParameters,
                    MethodGenerator::FLAG_PUBLIC,
                    $newBody,
                    $docBlock
                );
            }
        }

        return $sourceConstructorMethod;
    }


    /**
     * @param $name
     * @return null|MethodBinding
     */
    function getMethodBindingForMethod($name) {

        foreach ($this->methodBindingArray as $methodBinding) {
            if ($methodBinding->getFunctionName() == $name) {
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
    abstract function generate($savePath, $originalSourceClass);

    abstract function generateProxyMethodBody(MethodReflection $methodReflection, $weavingInfo);
}

 
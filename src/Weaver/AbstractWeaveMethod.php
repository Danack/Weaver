<?php


namespace Weaver;

use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

use Zend\Code\Reflection\MethodReflection;
use Zend\Code\Reflection\ParameterReflection;
use Zend\Code\Reflection\ClassReflection;


abstract class AbstractWeaveMethod {

    const PROXY = 'PROXY';
    const LAZY = 'LAZY';

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

    protected $weaving;

    private $fqcn = null;

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
    

    function getClosureFactoryName() {
        $closureFactoryName = '\\'.$this->sourceReflector->getNamespaceName().'\Closure'.$this->sourceReflector->getShortName().'Factory';

        return $closureFactoryName;
    }

    function getConstructorParamsString($constructorParameters, $includeTypeHints = false) {
        $string = '';
        $separator = '';

        foreach ($constructorParameters as $constructorParameter) {
            $string .= $separator;

            /** @var $constructorParameter ParameterGenerator */
            if ($includeTypeHints) {
                $typeHint = $constructorParameter->getType();
                if ($typeHint) {
                    $string .= '\\'.$typeHint.' ';
                }
            }

            $string .= '$'.$constructorParameter->getName();
            $separator = ', ';
        }

        return $string;
    }

    function setupClassName() {
        $this->generator->setName($this->getFQCN());

        if (array_key_exists('interfaces', $this->weaving) == true) {
            $this->generator->setImplementedInterfaces($this->weaving['interfaces']);
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
        $decoratorParamsWithType = $this->getConstructorParamsString($decoratorConstructorMethod->getParameters(), true);
        $decoratorUseParams = $this->getConstructorParamsString($decoratorConstructorMethod->getParameters());
        $objectParams = $this->getConstructorParamsString($sourceConstructorMethod->getParameters());
        $allParams = $this->getConstructorParamsString($constructorParameters);
        $closureFactoryName = $this->getClosureFactoryName();
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


    function saveFile($savePath) {

        $fqcn = $this->getFQCN();
        $filename = $savePath.'/'.$fqcn.'.php';
        $filename = str_replace('\\', '/', $filename);
        ensureDirectoryExists($filename);
        $written = file_put_contents($filename, "<?php\n".$this->generator->generate());

        if ($written == false) {
            throw new \RuntimeException("Failed to write file $filename.");
        }
    }



    function addProxyMethods($mode) {

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

            $newBody = $this->generateProxyMethodBody($method, $this->weaving);
            
//            if ($mode == self::PROXY) {
//                if (array_key_exists($name, $this->weaving) == false) {
//                    continue;
//                }
//                $body = $this->getProxiedBody($this->weaving[$name], $method);
//            }
//            else if ($mode == self::LAZY) {
//                $body = $this->generateLazyProxyMethodBody($method);
//
//            }
//            else {
//                throw new \RuntimeException("Unknown type $mode");
//            }
            
            if ($newBody == true) {
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
     * @param $savePath
     * @return string|null
     */
    abstract function generate($savePath, $originalSourceClass);

    abstract function generateProxyMethodBody(MethodReflection $methodReflection, $weavingInfo);
}

 
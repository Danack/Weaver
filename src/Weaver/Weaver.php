<?php


namespace Weaver;



use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

use Zend\Code\Reflection\MethodReflection;
use Zend\Code\Reflection\ParameterReflection;


use Zend\Code\Generator\ClassGenerator;



use Zend\Code\Reflection\ClassReflection;

\Intahwebz\Functions::load();

class Weaver {

    const PROXY = 'PROXY';
    const LAZY = 'LAZY';

    /**
     * @var ClassReflection
     */
    private $sourceReflector;

    /**
     * @var ClassReflection
     */
    private $decoratorReflector;

    /**
     * @var ClassGenerator
     */
    private $generator;
 
    private $weaving;
    
    private $closureFactories = array();

    function weaveClass($sourceClass, $decoratorClass, $weaving, $savePath) {
        $this->sourceReflector = new ClassReflection($sourceClass);
        $this->decoratorReflector = new ClassReflection($decoratorClass);
        $this->generator = new ClassGenerator();
        $this->weaving = $weaving;

        $fqcn = $this->setupClassName();
        $sourceConstructorMethod = $this->addProxyMethods(self::PROXY);
        $decoratorConstructorMethod = $this->addDecoratorMethods();
        $constructorParameters = $this->addProxyConstructor($sourceConstructorMethod, $decoratorConstructorMethod);
        $this->addPropertiesAndConstants();
        $this->saveFile($fqcn, $savePath);

        $this->generateFactoryClosure($fqcn, $constructorParameters, $sourceConstructorMethod, $decoratorConstructorMethod);
    }

    function addClosureFactory($function) {
        $this->closureFactories[] = $function;
    }
    
    function getClosureFactories() {
        return $this->closureFactories;
    }

    function getClosureFactoryName() {
        $closureFactoryName = '\\'.$this->sourceReflector->getNamespaceName().'\Closure'.$this->sourceReflector->getShortName().'Factory';
        
        return $closureFactoryName;
    }

    function generateFactoryClosure(
        $fqcn,
        $constructorParameters, 
        MethodReflection $sourceConstructorMethod, 
        MethodReflection $decoratorConstructorMethod
    ) {

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
    
        $this->addClosureFactory($function);
        
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
    
    
    /**
     * @param $sourceClass
     * @param $decoratorClass
     * @param $weaving
     * @param $savePath
     * @throws \RuntimeException
     */
    function lazyProxyClass($sourceClass, $decoratorClass, $weaving, $savePath) {

        $this->sourceReflector = new ClassReflection($sourceClass);
        $this->decoratorReflector = new ClassReflection($decoratorClass);
        $this->generator = new ClassGenerator();
        $this->weaving = $weaving;

        $fqcn = $this->setupClassName();
        $sourceConstructorMethod = $this->addProxyMethods(self::LAZY);
        $decoratorConstructorMethod = $this->addDecoratorMethods();
        $this->addInitMethod($sourceConstructorMethod, $decoratorConstructorMethod);
        $this->addPropertiesAndConstants();
        $this->saveFile($fqcn, $savePath);
    }



    function getProxiedBody($weavingInfo, MethodReflection $method) {
        $newBody = $weavingInfo[0]."\n";
        $newBody .= '$result = parent::'.$method->getName()."(";
        $parameters = $method->getParameters();
        $separator = '';

        foreach ($parameters as $reflectionParameter) {
            $newBody .= $separator.'$'.$reflectionParameter->getName();
            $separator = ', ';
        }

        $newBody .= ");\n";
        $newBody .= $weavingInfo[1]."\n\n";
        $newBody .= 'return $result;'."\n";

        return $newBody;
    }


    function addProxyConstructor(MethodReflection $sourceConstructorMethod, MethodReflection $decoratorConstructorMethod) {
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


    function addInitMethod(MethodReflection $sourceConstructorMethod, MethodReflection $decoratorConstructorMethod) {
        $initBody = 'if ($this->'.$this->weaving['lazyProperty'].' == null) {
            $this->lazyInstance = new \\'.$this->sourceReflector->getName().'(';

        $constructorParams = $this->addLazyConstructor($sourceConstructorMethod,
            $decoratorConstructorMethod);

        $initBody .= $constructorParams;

        $initBody .= ");\n}";

        $this->generator->addMethod(
            'init',
            array(),
            MethodGenerator::FLAG_PUBLIC,
            $initBody,
            ""
        );
    }
 

    function getProxiedName() {
        return $this->decoratorReflector->getShortName()."X".$this->sourceReflector->getShortName();
    }
    
    
    function setupClassName() {
        
        $namespace = $this->sourceReflector->getNamespaceName();
        $classname = $this->getProxiedName();

        if (strlen($namespace)) {
            $fqcn = $namespace.'\\'.$classname;
        }
        else {
            $fqcn = $classname;
        }
        $this->generator->setName($fqcn);

        if (array_key_exists('interfaces', $this->weaving) == true) {
            $this->generator->setImplementedInterfaces($this->weaving['interfaces']);
        }
        else {
            $this->generator->setExtendedClass('\\'.$this->sourceReflector->getName());
        }
        
        return $fqcn;
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
    

    function saveFile($fqcn, $savePath) {
        $filename = $savePath.'/'.$fqcn.'.php';
        $filename = str_replace('\\', '/', $filename);
        ensureDirectoryExists($filename);
        $written = file_put_contents($filename, "<?php\n".$this->generator->generate());
        
        if ($written == false) {
            throw new \RuntimeException("Failed to write file $filename.");
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

            if ($mode == self::PROXY) {
                if (array_key_exists($name, $this->weaving) == false) {
                    continue;
                }
                $body = $this->getProxiedBody($this->weaving[$name], $method);
            }
            else if ($mode == self::LAZY) {
                $body = $this->generateLazyProxyMethodBody($method);

            }
            else {
                throw new \RuntimeException("Unknown type $mode");
            }

            $this->generator->addMethod(
                $name,
                $generatedParameters,
                MethodGenerator::FLAG_PUBLIC,
                $body,
                $docBlock
            );
        }

        return $sourceConstructorMethod;
    }

    /**
     * @param $weavingInfo
     * @param MethodReflection $method
     * @return string
     */
    function generateLazyProxyMethodBody(MethodReflection $method) {
        $newBody = '';
        $newBody .= '$this->'.$this->weaving['init']."();\n";
        $newBody .= '$result = $this->'.$this->weaving['lazyProperty'].'->'.$method->getName()."(";
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
    
    function addLazyConstructor(
        MethodReflection $sourceConstructorMethod = null, 
        MethodReflection $decoratorConstructorMethod = null
    ) {

        $constructorBody = '';
        $constructorParams = '';
        $copyBody = '';

        $generatedParameters = array();

        if ($sourceConstructorMethod != null) {
            $parameters = $sourceConstructorMethod->getParameters();
            $separator = '';

            foreach ($parameters as $reflectionParameter) {
                $generatedParameters[] = ParameterGenerator::fromReflection($reflectionParameter);
                $constructorParams .= $separator.'$this->'.$reflectionParameter->getName();
                $separator = ', ';

                $this->generator->addProperty($reflectionParameter->getName());
                
                $copyBody .= '$this->'.$reflectionParameter->getName().' = $'.$reflectionParameter->getName().";\n";
            }
        }

        if ($decoratorConstructorMethod != null) {
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
        
        return $constructorParams;
    }


    function writeClosureFactories($filepath, $namespace, $filename, $closureFactories) {

        $fullFilename = $filepath.$filename.'.php';
        ensureDirectoryExists($fullFilename);

        $fileHandle = fopen($fullFilename, 'w');

        if ($fileHandle == false) {
            throw new \RuntimeException("Failed to open $fullFilename for writing.");
        }

        fwrite($fileHandle, "<?php\n\n");

        $namespaceLoader = <<< END
namespace $namespace {

    class $filename {
        public static function load() {
        }
    }
}



END;

        fwrite($fileHandle, $namespaceLoader);
        fwrite($fileHandle, "namespace {\n\n");

        foreach ($closureFactories as $closureFactory) {
            fwrite($fileHandle, $closureFactory);
            fwrite($fileHandle, "\n\n");
        }

        fwrite($fileHandle, "\n}\n");
        fclose($fileHandle);
    }
}

 
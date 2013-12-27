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

    function weaveClass($sourceClass, $decoratorClass, $weaving, $savePath) {
        $sourceReflector = new ClassReflection($sourceClass);
        $decoratorReflector = new ClassReflection($decoratorClass);
        $generator = new ClassGenerator();

        $fqcn = $this->setupClassName($generator, $weaving, $sourceReflector, $decoratorReflector);
        $sourceConstructorMethod = $this->addProxyMethods($generator, $sourceReflector, $weaving, self::PROXY);
        $decoratorConstructorMethod = $this->addDecoratorMethods($generator, $decoratorReflector, $weaving);
        $this->addProxyConstructor($generator, $sourceConstructorMethod, $decoratorConstructorMethod);
        $this->addPropertiesAndConstants($generator, $decoratorReflector);
        $this->saveFile($fqcn, $savePath, $generator);
    }


    /**
     * @param $sourceClass
     * @param $decoratorClass
     * @param $weaving
     * @param $savePath
     * @throws \RuntimeException
     */
    function lazyProxyClass($sourceClass, $decoratorClass, $weaving, $savePath) {

        $sourceReflector = new ClassReflection($sourceClass);
        $decoratorReflector = new ClassReflection($decoratorClass);
        $generator = new ClassGenerator();

        $fqcn = $this->setupClassName($generator, $weaving, $sourceReflector, $decoratorReflector);
        //$sourceConstructorMethod = $this->addSourceMethods($generator, $sourceReflector, $weaving);
        $sourceConstructorMethod = $this->addProxyMethods($generator, $sourceReflector, $weaving, self::LAZY);
        
        $decoratorConstructorMethod = $this->addDecoratorMethods($generator, $decoratorReflector, $weaving);
        $this->addInitMethod($weaving, $generator, $sourceReflector, $sourceConstructorMethod, $decoratorConstructorMethod);
        $this->addPropertiesAndConstants($generator, $decoratorReflector);
        $this->saveFile($fqcn, $savePath, $generator);
    }



    function modifyBody($weavingInfo, MethodReflection $method) {

        $newBody = '';

        $newBody .= $weavingInfo[0]."\n";
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





    function addProxyConstructor(ClassGenerator $generator, MethodReflection $sourceConstructorMethod, MethodReflection $decoratorConstructorMethod) {
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

        $generator->addMethod(
            '__construct',
            $generatedParameters,
            MethodGenerator::FLAG_PUBLIC,
            $constructorBody,
            ""
        );
    }


    function addInitMethod(
        $weaving,
        ClassGenerator $generator,
        ClassReflection $sourceReflector,
        $sourceConstructorMethod,
        $decoratorConstructorMethod
    ) {
        $initBody = 'if ($this->'.$weaving['lazyProperty'].' == null) {
            $this->lazyInstance = new \\'.$sourceReflector->getName().'(';

        $constructorParams = $this->addLazyConstructor($generator, $sourceConstructorMethod,
            $decoratorConstructorMethod);

        $initBody .= $constructorParams;

        $initBody .= ");\n}";

        $generator->addMethod(
            'init',
            array(),
            MethodGenerator::FLAG_PUBLIC,
            $initBody,
            ""
        );
    }
 




    
    function setupClassName(
        ClassGenerator $generator, 
        $weaving, 
        ClassReflection $sourceReflector, 
        ClassReflection $decoratorReflector
    ) {
        
        $namespace = $sourceReflector->getNamespaceName();
        $classname = $decoratorReflector->getShortName()."X".$sourceReflector->getShortName();

        if (strlen($namespace)) {
            $fqcn = $namespace.'\\'.$classname;
        }
        else {
            $fqcn = $classname;
        }
        $generator->setName($fqcn);

        if (array_key_exists('interfaces', $weaving) == true) {
            $generator->setImplementedInterfaces($weaving['interfaces']);
        }
        else {
            $generator->setExtendedClass('\\'.$sourceReflector->getName());
        }
        
        return $fqcn;
    }
    

    function addPropertiesAndConstants(ClassGenerator $generator, ClassReflection $decoratorReflector) {
        $constants = $decoratorReflector->getConstants();

        foreach ($constants as $name => $value) {
            $generator->addProperty($name, $value, PropertyGenerator::FLAG_CONSTANT);
        }

        $properties = $decoratorReflector->getProperties();

        foreach ($properties as $property) {
            $newProperty = PropertyGenerator::fromReflection($property);
            $generator->addPropertyFromGenerator($newProperty);
        }
    }
    

    function saveFile($fqcn, $savePath, $generator) {

        $filename = $savePath.'/'.$fqcn.'.php';

        $filename = str_replace('\\', '/', $filename);

        ensureDirectoryExists($filename);

        $written = file_put_contents($filename, "<?php\n".$generator->generate());

        if ($written == false) {
            throw new \RuntimeException("Failed to write file $filename.");
        }
    }
    
  
    
    
    
    function addDecoratorMethods(ClassGenerator $generator, ClassReflection $decoratorReflector, $weaving) {

        $decoratorConstructorMethod = null;
        
        $methods = $decoratorReflector->getMethods();

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

            $generator->addMethod(
                $name,
                $generatedParameters,
                MethodGenerator::FLAG_PUBLIC,
                $method->getBody(),
                $method->getDocBlock()
            );
        }
        
        return $decoratorConstructorMethod;
    }



    function addProxyMethods(ClassGenerator $generator, ClassReflection $sourceReflector, $weaving, $mode) {

        $sourceConstructorMethod = null;

        $methods = $sourceReflector->getMethods();

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
                if (array_key_exists($name, $weaving) == false) {
                    continue;
                }
                $body = $this->modifyBody($weaving[$name], $method);
            }
            else if ($mode == self::LAZY) {
                $body = $this->generateLazyProxyMethodBody($weaving, $method);

            }
            else {
                throw new \RuntimeException("Unknown type $mode");
            }

            $generator->addMethod(
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
    function generateLazyProxyMethodBody($weavingInfo, MethodReflection $method) {
        $newBody = '';
        $newBody .= '$this->'.$weavingInfo['init']."();\n";
        $newBody .= '$result = $this->'.$weavingInfo['lazyProperty'].'->'.$method->getName()."(";
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
        ClassGenerator $generator, 
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

                $generator->addProperty($reflectionParameter->getName());
                
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

        $generator->addMethod(
            '__construct',
            $generatedParameters,
            MethodGenerator::FLAG_PUBLIC,
            $constructorBody,
            ""
        );
        
        return $constructorParams;
    }
}

 
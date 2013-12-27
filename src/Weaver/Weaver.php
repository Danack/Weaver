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


    function weaveClass($sourceClass, $decoratorClass, $weaving, $savePath) {

        $sourceReflector = new ClassReflection($sourceClass);

        $decoratorReflector = new ClassReflection($decoratorClass);

        $generator = new ClassGenerator();

        $namespace = $sourceReflector->getNamespaceName();
        $classname = $decoratorReflector->getShortName()."X".$sourceReflector->getShortName();
        

        if (strlen($namespace)) {
            $fqcn = $namespace.'\\'.$classname;
        }
        else {
            $fqcn = $classname;
        }

        $generator->setName($fqcn);

        $generator->setExtendedClass('\\'.$sourceReflector->getName());

        $methods = $sourceReflector->getMethods();

        $sourceConstructorMethod = null;
        $decoratorConstructorMethod = null;

        foreach ($methods as $method) {

            $name = $method->getName();

            if ($name == '__construct') {
                $sourceConstructorMethod = $method;
                continue;
            }

            $parameters = $method->getParameters();
            $flags = MethodGenerator::FLAG_PUBLIC;
            $body = $method->getBody();
            $docBlock = $method->getDocBlock();

            if ($docBlock) {
                $docBlock = DocBlockGenerator::fromReflection($docBlock);
            }

            $generatedParameters = array();

            foreach ($parameters as $reflectionParameter) {
                $generatedParameters[] = ParameterGenerator::fromReflection($reflectionParameter);
            }

            if (array_key_exists($name, $weaving) == true) {
                $body = $this->modifyBody($weaving[$name], $method);

                $generator->addMethod(
                    $name,
                    $generatedParameters,
                    $flags,
                    $body,
                    $docBlock
                );
            }
        }

        $methods = $decoratorReflector->getMethods();

        foreach ($methods as $method) {

            $name = $method->getName();

            if ($name == '__construct') {
                $decoratorConstructorMethod = $method;
                continue;
            }

            $parameters = $method->getParameters();
            $flags = MethodGenerator::FLAG_PUBLIC;
            $body = $method->getBody();
            $docBlock = $method->getDocBlock();

            $generatedParameters = array();

            foreach ($parameters as $reflectionParameter) {
                $generatedParameters[] = ParameterGenerator::fromReflection($reflectionParameter);
            }

            $generator->addMethod(
                $name,
                $generatedParameters,
                $flags,
                $body,
                $docBlock
            );
        }


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


        $constants = $decoratorReflector->getConstants();

        foreach ($constants as $name => $value) {
            $generator->addProperty($name, $value, PropertyGenerator::FLAG_CONSTANT);
        }

        $properties = $decoratorReflector->getProperties();

        foreach ($properties as $property) {
            $newProperty = PropertyGenerator::fromReflection($property);
            $generator->addPropertyFromGenerator($newProperty);
        }

        $generator->addUse('Intahwebz\Timer');

        $filename = $savePath.'/'.$generator->getNamespaceName().'/'.$generator->getName().'.php';

        $filename = str_replace('\\', '/', $filename);

        ensureDirectoryExists($filename);

        $written = file_put_contents($filename, "<?php\n".$generator->generate());

        if ($written == false) {
            throw new \RuntimeException("Failed to write file $filename.");
        }
    }
}

 
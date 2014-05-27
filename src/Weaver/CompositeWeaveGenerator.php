<?php


namespace Weaver;

use Zend\Code\Generator\ClassGenerator;

use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Reflection\MethodReflection;
use Zend\Code\Reflection\ClassReflection;

use Zend\Code\Generator\AbstractMemberGenerator;


class CompositeWeaveGenerator implements WeaveGenerator {

    /**
     * @var CompositeWeaveInfo
     */
    private $weaveInfo;

    /**
     * @var \Zend\Code\Generator\ClassGenerator
     */
    protected $generator;

    /**
     * @var ClassReflection[]
     */
    private $compositeClassReflectionArray = [];
    
    private $containerClassReflection;
    

    /**
     * @param $sourceClass
     * @param $decoratorClass
     * @param $methodBindingArray
     * @internal param \Weaver\MethodBinding[] $methodBinding
     */
    function __construct(CompositeWeaveInfo $weaveInfo) {
        $this->generator = new ClassGenerator();
        $this->weaveInfo = $weaveInfo;

        $this->containerClassReflection = new ClassReflection($weaveInfo->getDecoratorClass());
        foreach ($weaveInfo->getComposites() as $composite) {
            $this->compositeClassReflectionArray[] = new ClassReflection($composite);
        }
    }

    /**
     * @param $outputDir
     */
    function writeClass($outputDir, $outputClassname = null) {

        $interfaces = $this->containerClassReflection->getInterfaces();

        $function = function (ClassReflection $interfaceRefelection) {
            return $interfaceRefelection->getName();
        };
        
        $interfaces = array_map($function, $interfaces);
        $this->generator->setImplementedInterfaces($interfaces);
        $this->addPropertiesAndConstants();
        $this->addConstructorMethod();
        $this->addMethods();
        $this->addEncapsulatedMethods();

        $fqcn = $this->getFQCN();
        
        if ($outputClassname) {
            $fqcn = $outputClassname;
        }

        $this->generator->setName($fqcn);
        $text = $this->applyHacks($this->generator->generate());
        \Weaver\saveFile($outputDir, $fqcn, $text);

        return $fqcn;
    }

    /**
     * @return array
     */
    function addConstructorMethod() {
        /**
         * ParameterReflection[]
         */
        $constructorParametersGeneratorArray = [];

        $weaveConstructorBody = '';

        foreach ($this->compositeClassReflectionArray as $sourceReflector) {
            $parameterGenerator = new ParameterGenerator();
            $paramName = lcfirst($sourceReflector->getShortName());
            $parameterGenerator->setName($paramName);
            $parameterGenerator->setType($sourceReflector->getName());
            $constructorParametersGeneratorArray[] = $parameterGenerator;
            $weaveConstructorBody .= "\$this->$paramName = \$$paramName;\n";
        }

        foreach ($this->containerClassReflection->getMethods() as $methodReflection) {
            if (strcmp($methodReflection->getName(), '__construct') === 0) {

                $methodGenerator = MethodGenerator::fromReflection($methodReflection);
                $modifiedConstructorName = 'construct_'.getClassName($methodReflection->getName());
                $methodGenerator->setName($modifiedConstructorName);
                $this->generator->addMethodFromGenerator($methodGenerator);
                $weaveConstructorBody .= "\t\t\$this->".$modifiedConstructorName.'(';

                $separator = '';
                $parameters = $methodReflection->getParameters();
                foreach ($parameters as $reflectionParameter) {
                    $constructorParametersGeneratorArray[] = ParameterGenerator::fromReflection($reflectionParameter);
                    $weaveConstructorBody .= $separator.'$'.$reflectionParameter->getName();
                    $separator = ', ';
                }
                $weaveConstructorBody .= ");\n";                
            }
        }

        $this->generator->addMethod(
            '__construct',
            $constructorParametersGeneratorArray,
            MethodGenerator::FLAG_PUBLIC,
            $weaveConstructorBody
        );

        return [];
    }

    /**
     * @return string
     */
    function getNamespaceName() {
        return $this->containerClassReflection->getNamespaceName();
    }

    /**
     * @return string
     */
    function getProxiedName() {
        $proxiedName = $this->containerClassReflection->getShortName();
        foreach ($this->compositeClassReflectionArray as $sourceReflector) {
            $proxiedName .= $sourceReflector->getShortName();
        }

        return $proxiedName;
    }

    /**
     * @param ClassReflection $sourceReflector
     */
    private function addMethodFromReflection(ClassReflection $sourceReflector) {

        $methods = $sourceReflector->getMethods();

        foreach ($methods as $method) {
            $name = $method->getName();
            $methodGenerator = MethodGenerator::fromReflection($method);

            if ($name == '__construct') {
                //Constructors are handled separately.
                continue;
            }

            if (array_key_exists($name, $this->weaveInfo->getEncapsulateMethods()) == true) {
                $methodGenerator->setVisibility(\Zend\Code\Generator\AbstractMemberGenerator::VISIBILITY_PRIVATE);
                $methodGenerator->setName($name.getClassName($sourceReflector->getName()));
            }

            $this->generator->addMethodFromGenerator($methodGenerator);
        }
    }

    /**
     * @param ClassReflection $sourceReflector
     */
    private function addProxiedMethodsFromReflection(ClassReflection $sourceReflector) {

        $methods = $sourceReflector->getMethods();

        foreach ($methods as $method) {
            $name = $method->getName();
            $methodGenerator = MethodGenerator::fromReflection($method);

            if ($name == '__construct') {
                //Constructors are handled separately.
                continue;
            }

            if (array_key_exists($name, $this->weaveInfo->getEncapsulateMethods()) == true) {
                continue;
            }

            $modifiers = $method->getModifiers();

            if (($modifiers & \ReflectionMethod::IS_PUBLIC) == false ||
                ($modifiers & \ReflectionMethod::IS_STATIC) == true) {
                continue;
            }

            $instanceName = lcfirst($sourceReflector->getShortName());
            
            $methodBody = "
                return \$this->$instanceName->$name();
            ";
            
            $methodGenerator->setBody($methodBody);
            $this->generator->addMethodFromGenerator($methodGenerator);
        }
    }
    
    
    /**
     * @return null|MethodReflection
     */
    private function addMethods() {
        $this->addMethodFromReflection($this->containerClassReflection);
        foreach ($this->compositeClassReflectionArray as $sourceReflector) {
            $this->addProxiedMethodsFromReflection($sourceReflector);
        }
    }

    /**
     * Adds the properties and constants from the decorating class to the
     * class being weaved.
     * @param $originalSourceClass
     */
    private function addPropertiesAndConstants() {
        $this->addPropertiesAndConstantsForReflector($this->containerClassReflection);
    }

    /**
     * @param $sourceCode
     * @return mixed
     */
    private function applyHacks($sourceCode) {
        $sourceCode = str_replace("(Intahwebz\\", "(\\Intahwebz\\", $sourceCode);
        $sourceCode = str_replace("(Example\\", "(\\Example\\", $sourceCode);
        $sourceCode = str_replace(", Example\\", ", \\Example\\", $sourceCode);
        $sourceCode = str_replace("(ImagickDemo\\ControlElement\\", "(\\ImagickDemo\\ControlElement\\", $sourceCode);
        $sourceCode = str_replace(", ImagickDemo\\ControlElement\\", ", \\ImagickDemo\\ControlElement\\", $sourceCode);
        $sourceCode = str_replace('implements Example\CompositeInterface', 'implements \Example\CompositeInterface', $sourceCode);
        $sourceCode = str_replace('implements ImagickDemo\Control', 'implements \ImagickDemo\Control', $sourceCode);

        return $sourceCode;
    }

    /**
     * @param ClassReflection $reflector
     * @param $originalSourceClass
     */
    private function addPropertiesAndConstantsForReflector(ClassReflection $reflector) {
        $constants = $reflector->getConstants();
        foreach ($constants as $name => $value) {
            $this->generator->addProperty($name, $value, PropertyGenerator::FLAG_CONSTANT);
        }

        $properties = $reflector->getProperties();
        foreach ($properties as $property) {
            $newProperty = PropertyGenerator::fromReflection($property);
            $this->generator->addPropertyFromGenerator($newProperty);
        }
    }

    /**
     * @throws WeaveException
     */
    function addEncapsulatedMethods() {
        foreach ($this->weaveInfo->getEncapsulateMethods() as $encapsulatedMethod => $resultType) {

            switch($resultType) {
                case('string'): {
                    $body = "\$result = '';\n";
                    foreach ($this->compositeClassReflectionArray as $compositeClassReflection) {
                        $paramName = lcfirst($compositeClassReflection->getShortName());
                        //TODO - add params
                        $body .= '    $result .= $this->'.$paramName.'->'.$encapsulatedMethod."();\r\n";
                    }
                    break;
                }

                case('array'): {
                    $body = "\$result = [];\n";
                    foreach ($this->compositeClassReflectionArray as $compositeClassReflection) {
                        $paramName = lcfirst($compositeClassReflection->getShortName());
                        $body .= '    $result = array_merge($result, $this->'.$paramName.'->'.$encapsulatedMethod."());\r\n";
                    }
                    break;
                }

                default:{
                    throw new WeaveException("Unknown resultType [$resultType]");
                    break;
                }
            }

            $body .= "\n";
            $body .= "return \$result;\n";
            
            $methodGenerator = new MethodGenerator(
                $encapsulatedMethod, //          $name = null, 
                [], 
                MethodGenerator::FLAG_PUBLIC, //$flags = self::FLAG_PUBLIC, 
                $body
                //$docBlock = null
            );

            $this->generator->addMethodFromGenerator($methodGenerator);
        }
    }
    

    /**
     * @return string
     */
    function getFQCN() {
        $namespace = $this->getNamespaceName();
        $classname = $this->getProxiedName();

        $return = $classname;
        
        if (strlen($namespace)) {
            $return = $namespace.'\\'.$return;
        }

        return $return;
    }
}

 
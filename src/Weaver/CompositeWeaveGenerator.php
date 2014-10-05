<?php


namespace Weaver;

use Danack\Code\Generator\ClassGenerator;
use Danack\Code\Generator\MethodGenerator;
use Danack\Code\Generator\ParameterGenerator;
use Danack\Code\Generator\PropertyGenerator;
use Danack\Code\Reflection\MethodReflection;
use Danack\Code\Reflection\ClassReflection;
use Danack\Code\Reflection\ParameterReflection;

class CompositeWeaveGenerator {

    /**
     * @var CompositeWeaveInfo
     */
    private $weaveInfo;

    /**
     * @var \Danack\Code\Generator\ClassGenerator
     */
    protected $generator;

    /**
     * @var ClassReflection[]
     */
    private $compositeClassReflectionArray = [];
    
    private $containerClassReflection;


    /**
     * @param $composites
     * @param CompositeWeaveInfo $weaveInfo
     * @internal param $sourceClass
     * @internal param $decoratorClass
     * @internal param $methodBindingArray
     * @internal param \Weaver\MethodBinding[] $methodBinding
     */
    function __construct($composites, CompositeWeaveInfo $weaveInfo) {
        $this->generator = new ClassGenerator();
        $this->weaveInfo = $weaveInfo;

        $this->containerClassReflection = new ClassReflection($weaveInfo->getDecoratorClass());
        foreach ($composites as $composite) {
            $this->compositeClassReflectionArray[] = new ClassReflection($composite);
        }
    }

    /**
     * @throws WeaveException
     * @internal param $outputDir
     * @return \Weaver\WeaveResult
     */
    function generate() {
        $interfaces = $this->containerClassReflection->getInterfaces();

        $function = function (ClassReflection $interfaceReflection) {
            return $interfaceReflection->getName();
        };
        
        $interfaces = array_map($function, $interfaces);
        $this->generator->setImplementedInterfaces($interfaces);
        $this->addPropertiesAndConstantsForContainer();
        $this->addConstructorMethod();
        $this->addMethods();
        $this->addEncapsulatedMethods();
        $this->generator->setName($this->getFQCN());

        //TODO - generate a FactoryGenerator
        return new WeaveResult($this->generator, null);
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
                $weaveConstructorBody .= "\$this->".$modifiedConstructorName.'(';

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
//                $methodGenerator->setVisibility(\Danack\Code\Generator\AbstractMemberGenerator::VISIBILITY_PRIVATE);
//                $methodGenerator->setName($name.getClassName($sourceReflector->getName()));
                continue;
            }

            $this->generator->addMethodFromGenerator($methodGenerator);
        }
    }

//    /**
//     * @param ClassReflection $sourceReflector
//     */
//    private function addProxiedMethodsFromReflection(ClassReflection $sourceReflector) {
//
//        $methods = $sourceReflector->getMethods();
//
//        foreach ($methods as $method) {
//            $name = $method->getName();
//            $methodGenerator = MethodGenerator::fromReflection($method);
//
//            if ($name == '__construct') {
//                //Constructors are handled separately.
//                continue;
//            }
//
//            if (array_key_exists($name, $this->weaveInfo->getEncapsulateMethods()) == true) {
//                continue;
//            }
//
//            $modifiers = $method->getModifiers();
//
//            if (($modifiers & \ReflectionMethod::IS_PUBLIC) == false ||
//                ($modifiers & \ReflectionMethod::IS_STATIC) == true) {
//                continue;
//            }
//
//            $instanceName = lcfirst($sourceReflector->getShortName());
//            
//            $methodBody = "
//                return \$this->$instanceName->$name();
//            ";
//            
//            $methodGenerator->setBody($methodBody);
//            $this->generator->addMethodFromGenerator($methodGenerator);
//        }
//    }
    
    
    /**
     * @return null|MethodReflection
     */
    private function addMethods() {
        $this->addMethodFromReflection($this->containerClassReflection);
//        foreach ($this->compositeClassReflectionArray as $sourceReflector) {
//            $this->addProxiedMethodsFromReflection($sourceReflector);
//        }
    }

    /**
     * Adds the properties and constants from the decorating class to the
     * class being weaved.
     * @internal param $originalSourceClass
     */
    private function addPropertiesAndConstantsForContainer() {
        $this->addPropertiesAndConstantsFromReflection($this->containerClassReflection);

        foreach ($this->compositeClassReflectionArray as $compositeClassReflection) {
            $paramName = $this->getComponentName($compositeClassReflection);
            $newProperty = new PropertyGenerator($paramName, null, PropertyGenerator::FLAG_PRIVATE);
            $this->generator->addPropertyFromGenerator($newProperty);
        }
    }


    /**
     * @param ClassReflection $reflector
     * @internal param $originalSourceClass
     */
    private function addPropertiesAndConstantsFromReflection(ClassReflection $reflector) {
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


    function getComponentName(ClassReflection $compositeClassReflection) {
        return lcfirst($compositeClassReflection->getShortName());
    }
    
    /**
     * @throws WeaveException
     */
    function addEncapsulatedMethods() {
        
        /**
         * @var  $encapsulatedMethod MethodGenerator
         * @var  $resultType
         */

        //$decoratorClass = $this->weaveInfo->getDecoratorClass();
        
        
        foreach ($this->weaveInfo->getEncapsulateMethods() as $encapsulatedMethod => $resultType) {

  //          $params = $encapsulatedMethod->getParameters();

            $params = [];
            
            if ($this->containerClassReflection) {
                
                if ($this->containerClassReflection->hasMethod($encapsulatedMethod)) {
                    $containerMethod = $this->containerClassReflection->getMethod($encapsulatedMethod);
                    $params = $containerMethod->getParameters();
                }
            }

            
//
//            if (!$encapsulatedMethod) {
//                throw new WeaveException("Container class does not have the method $encapsulatedMethod cannot use it to enacapsulate.");
//            }


            $getParamName = function(ParameterReflection $paramReflection) {
                return '$'.$paramReflection->getName();
            };
            
            //TODO - generate param string
            $paramString = implode(', ', array_map($getParamName, $params));
            
            switch($resultType) {

                case(CompositeWeaveInfo::RETURN_STRING): {
                    $body = "\$result = '';\n";
                    foreach ($this->compositeClassReflectionArray as $compositeClassReflection) {
                        $paramName = $this->getComponentName($compositeClassReflection);
                 
                        $body .= sprintf(
                            "\$result .= \$this->%s->%s(%s);\r\n",
                            $paramName,
                            $encapsulatedMethod, 
                            $paramString
                        );
                    }
                    break;
                }

                case(CompositeWeaveInfo::RETURN_ARRAY): {
                    $body = "\$result = [];\n";
                    foreach ($this->compositeClassReflectionArray as $compositeClassReflection) {
                        $paramName = $this->getComponentName($compositeClassReflection);
                        //TODO - add params
                        $body .= sprintf(
                            "\$result = array_merge(\$result, \$this->%s->%s(%s));\r\n",
                            $paramName,
                            $encapsulatedMethod,
                            $paramString
                        );
                    }
                    break;
                }

                case(CompositeWeaveInfo::RETURN_BOOLEAN): {
                    $body = "\$result = true;\n";
                    foreach ($this->compositeClassReflectionArray as $compositeClassReflection) {
                        $paramName = $this->getComponentName($compositeClassReflection);
                        //TODO - add params
                        $body .= sprintf(
                            "\$result = \$result && \$this->%s->%s(%s);\r\n",
                            $paramName,
                            $encapsulatedMethod,
                            $paramString
                        );
                    }
                    break;
                }        

                default:{
                    throw new WeaveException(
                        "Unknown resultType [$resultType]",
                        WeaveException::UNKNOWN_METHOD_RETURN_TYPE
                    );
                    break;
                }
            }

            $body .= "\n";
            $body .= "return \$result;\n";


            $makeGenerators = function (ParameterReflection $paramReflection) {
                return ParameterGenerator::fromReflection($paramReflection);
            };
            
            
            $paramGenerators = array_map($makeGenerators, $params);
            
            
            $methodGenerator = new MethodGenerator(
                $encapsulatedMethod,
                $paramGenerators, 
                MethodGenerator::FLAG_PUBLIC, 
                $body
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

 
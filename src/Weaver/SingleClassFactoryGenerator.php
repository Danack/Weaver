<?php


namespace Weaver;

use Danack\Code\Generator\MethodGenerator;
use Danack\Code\Generator\ParameterGenerator;
use Danack\Code\Generator\DocBlockGenerator;

class SingleClassFactoryGenerator extends FactoryGenerator {

    /**
     * @param $fqcn
     */
    function addCreateMethod($fqcn) {
        $sourceParameters = $this->getSourceClassConstructorParams();
        $sourceParametersAsString = getParamsAsString($sourceParameters);

        $decoratorParameters = $this->getDecoratorClassConstructorParams();
        $decoratorParametersAsString = getParamsAsString($decoratorParameters, false, true);

        $sourceParameterGenerators = [];
        foreach ($sourceParameters as $sourceParameter) {
            $sourceParameterGenerators[] = ParameterGenerator::fromReflection($sourceParameter);
        }

        $createMethod = new MethodGenerator('create');
        $createMethod->setParameters($sourceParameterGenerators);

        $body = sprintf(
            "return new \\%s(%s, %s);",
            $fqcn,
            $sourceParametersAsString,
            $decoratorParametersAsString
        );

        $createMethod->setBody($body);
        $createMethod->setDocBlock("@return \\$fqcn");        
        $this->generator->addMethodFromGenerator($createMethod);
    }

    /**
     * @param $factoryClass
     * @param $decoratorParameters
     * @param $sourceParameters
     * @param $fqcn
     * @return string
     */
    function generateClosureFactoryBody($factoryClass, $decoratorParameters, $sourceParameters, $fqcn) {

        $closureFactoryName = $factoryClass;

        $addedParams = $this->getAddedParameters($decoratorParameters, $sourceParameters);
        $useString = '';

        //TODO - switch this to use DanackCode
        $closureParamsString = getParamsAsString($sourceParameters, true);

        if (count($addedParams)) {
            $useString = "use (".getParamsAsString($addedParams).")";
        }

        //TODO - need to not have dupes.
        $allParams = array_merge($sourceParameters, $decoratorParameters);
        $allParamsString = getParamsAsString($allParams);
        $generatedClassname = $fqcn;

        $body = "
        \$closure = function ($closureParamsString) $useString {
            \$object = new \\$generatedClassname(
                $allParamsString
            );
    
            return \$object;
        };
    
        return new $closureFactoryName(\$closure);  
    ";

        return $body;
    }
}

 
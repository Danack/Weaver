<?php


namespace Weaver;

use Danack\Code\Generator\MethodGenerator;
use Danack\Code\Generator\ParameterGenerator;

class ImplementFactoryGenerator extends FactoryGenerator {

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
        $allParamsString = getParamsAsString($decoratorParameters);
        $generatedClassname = $fqcn;

        $body = "
        \$closure = function ($closureParamsString) $useString {
        
        \$sourceInstance = new \\".$this->sourceReflection->getName()."($closureParamsString);
        
            \$object = new \\$generatedClassname(
                \$sourceInstance,
                $allParamsString
            );
    
            return \$object;
        };
    
        return new $closureFactoryName(\$closure);  
    ";

        return $body;
    }

    function addCreateMethod($fqcn) {


        $sourceParameters = $this->getSourceClassConstructorParams();
        $sourceParametersAsString = getParamsAsString($sourceParameters);

        $decoratorParameters = $this->getDecoratorClassConstructorParams();
        $decoratorParametersAsString = getParamsAsString($decoratorParameters, false, true);
        
        $parameterGenerators = [];
        foreach ($sourceParameters as $sourceParameter) {
            $parameterGenerators[] = ParameterGenerator::fromReflection($sourceParameter);
        }

        $createMethod = new MethodGenerator('create');
        $createMethod->setParameters($parameterGenerators);

        $bodyText = <<< END
        \$sourceInstance = new \\%s(%s);
        
        \$object = new \\%s(
            \$sourceInstance,
            %s
        );
    
        return \$object;
END;

        $body = sprintf(
            $bodyText,
            $this->sourceReflection->getName(),
            $sourceParametersAsString,
            $fqcn,
            $decoratorParametersAsString
        );

        $createMethod->setBody($body);
        $this->generator->addMethodFromGenerator($createMethod);
    }

}

 
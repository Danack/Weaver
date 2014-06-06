<?php


namespace Weaver;


use Danack\Code\Generator\ClassGenerator;
use Danack\Code\Reflection\ClassReflection;

/**
 * @param $savePath
 * @throws \RuntimeException
 */
function saveFile($savePath, $fqcn, $text) {

    $filename = str_replace('\\', '/', $fqcn);

    $fileHeader = <<< END
<?php

//Auto-generated by Weaver - https://github.com/Danack/Weaver
//
//Do not be surprised if any changes to this file are over-written.
//

END;

    $outputFilename = $savePath.'/'.$filename.'.php';
    @mkdir(dirname($outputFilename), 0777, true);
    $written = @file_put_contents($outputFilename, $fileHeader.$text);

    if ($written == false) {
        throw new WeaveException("Failed to write file $filename.");
    }
}




class WeaveResult {

    /**
     * @var \Danack\Code\Generator\ClassGenerator
     */
    public $generator;

    /**
     * @param ClassGenerator $generator
     * @param ClassReflection $sourceReflection
     * @param ClassReflection $decorationReflection
     */
    function __construct(ClassGenerator $generator, FactoryGenerator $factoryGenerator = null) {
        $this->generator = $generator;
        $this->factoryGenerator = $factoryGenerator;
    }

    public function setFQCN($fqcn) {
        $this->generator->setFQCN($fqcn);
    }
    
    public function writeFile($outputDir, $outputClassname = null) {
        if ($outputClassname) {
            $this->setFQCN($outputClassname);
        }

        saveFile($outputDir, $this->generator->getFQCN(), $this->generator->generate());

        return $this->generator->getFQCN();
    }
    
    
    public function generateFactory($factoryClass) {
        return $this->factoryGenerator->generate($factoryClass, $this->generator->getFQCN());
    }
}

 
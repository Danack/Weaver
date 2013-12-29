<?php


namespace Weaver;

\Intahwebz\Functions::load();

class Weaver {

    /**
     * @var string[]
     */
    private $closureFactories = array();


    /**
     * @param $sourceClass
     * @param $weaveInfoArray WeaveInfo[]
     * @param $savePath
     */
    function weaveClass($sourceClass, $weaveInfoArray, $savePath) {
        
        $originalSourceClass = $sourceClass;

        foreach ($weaveInfoArray as $weaveInfo) {
            $decoratorClass = $weaveInfo->getDecoratorClass();
            
            $methodBindingArray = $weaveInfo->getMethodBindingArray();
            
            if ($weaveInfo instanceof \Weaver\LazyWeaveInfo) {
                $extendWeaver = new InstanceWeaveMethod($sourceClass, $decoratorClass, $weaveInfo);
            }
            else {
                $extendWeaver = new ExtendWeaveMethod($sourceClass, $decoratorClass, $methodBindingArray);
            }

            $sourceClass = $extendWeaver->getFQCN();
            $factoryClosure = $extendWeaver->generate($savePath, $originalSourceClass);
            $this->addClosureFactory($factoryClosure);
        }
    }

    function addClosureFactory($function) {
        $this->closureFactories[] = $function;
    }
    
    function getClosureFactories() {
        return $this->closureFactories;
    }



    function writeClosureFactories($filepath, $namespace, $filename, $closureFactories) {
        $fullFilename = $filepath.'/'.$namespace.'/'.$filename.'.php';
        ensureDirectoryExists($fullFilename);

        $fileHandle = fopen($fullFilename, 'w');

        if ($fileHandle == false) {
            throw new \RuntimeException("Failed to open $fullFilename for writing.");
        }


        $fileHeader = <<< END
<?php
//Auto-generated by Weaver - https://github.com/Danack/Weaver
//
//Do not be surprised if any changes to this file are over-written.



END;
        fwrite($fileHandle, $fileHeader);
        
        
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

 
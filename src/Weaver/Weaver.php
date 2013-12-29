<?php


namespace Weaver;

\Intahwebz\Functions::load();

class Weaver {
    
    private $closureFactories = array();

    function extendWeaveClass($sourceClass, $weavingInfoArray, $savePath) {
        
        $originalSourceClass = $sourceClass;
        

        foreach ($weavingInfoArray as $weavingInfo) {
            $decoratorClass = $weavingInfo[0];
            $weaving = $weavingInfo[1];
            $extendWeaver = new ExtendWeaveMethod($sourceClass, $decoratorClass, $weaving, $savePath);
            $sourceClass = $extendWeaver->getFQCN();
            $factoryClosure = $extendWeaver->generate($savePath, $originalSourceClass);
            $this->addClosureFactory($factoryClosure);
        }
    }
    
    function instanceWeaveClass($sourceClass, $decoratorClass, $weaving, $savePath) {

        $originalSourceClass = $sourceClass;
        
        $extendWeaver = new InstanceWeaveMethod($sourceClass, $decoratorClass, $weaving, $savePath);
        $factoryClosure = $extendWeaver->generate($savePath, $originalSourceClass);
        $this->addClosureFactory($factoryClosure);
    }

    function addClosureFactory($function) {
        $this->closureFactories[] = $function;
    }
    
    function getClosureFactories() {
        return $this->closureFactories;
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

 
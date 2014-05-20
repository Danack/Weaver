<?php


namespace Weaver;

use Zend\Code\Generator\ParameterGenerator;

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
    $written = file_put_contents($outputFilename, $fileHeader.$text);

    if ($written == false) {
        throw new \RuntimeException("Failed to write file $filename.");
    }
}



interface WeaveGenerator {
    function writeClass($directory, $outputClassname = null);
}

 
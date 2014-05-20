<?php

namespace {

    $GLOBALS['mockFilePutContents'] = false;
}


namespace Weaver {


function file_put_contents($outputFilename, $text) {
    if (isset($GLOBALS['mockFilePutContents']) && $GLOBALS['mockFilePutContents'] === true) {
        return false;
    } else {
        return call_user_func_array('\file_put_contents', func_get_args());
    }
}


class UtilTest extends \PHPUnit_Framework_TestCase {

    private $outputDir;

    function __construct() {
        $this->outputDir = dirname(__FILE__).'/../../generated/';
    }

    function testExtendWeave_cacheProxy() {

        $this->setExpectedException('Weaver\WeaveException');
        
        $cacheWeaveInfo = new ExtendWeaveInfo(
            'Example\TestClass',
            'Weaver\Weave\CacheProxy',
            []
        );

        $GLOBALS['mockFilePutContents'] = true;
        
        $weaver = new ExtendWeaveGenerator($cacheWeaveInfo);
        $weaver->writeClass($this->outputDir);
    }
    
}

}
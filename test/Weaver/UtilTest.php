<?php

namespace {

    $GLOBALS['mockFilePutContents'] = false;
}


namespace Weaver {

    //TODO Replace this with the VFS mocking class
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

    /** 
     * This test exists solely to get coverage of when writing the output fails. 
     * @TODO - this test is slightly out of date. The non-prototype based decorator is no longer
     * really usable. The test still tests okay.
     */
    function testExtendWeave_cacheDecorator() {
        $this->setExpectedException(
            'Weaver\WeaveException',
            '',
            \Weaver\WeaveException::IO_ERROR
        );
        $cacheWeaveInfo = new ExtendWeaveInfo(
            'Weaver\Weave\CacheDecorator',
            []
        );

        $GLOBALS['mockFilePutContents'] = true;
        $result = Weaver::weave('Example\TestClass', $cacheWeaveInfo);
        $result->writeFile($this->outputDir, 'Example\CachedTwitter');
    }

    /**
     * Check that passing in invalid weaveInfo 
     * @throws WeaveException
     */
    function testUnknownWeaverType() {
        $this->setExpectedException('Weaver\WeaveException');
        $result = Weaver::weave('Example\TestClass', new \stdClass());
    }
    
}

}
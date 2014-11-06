<?php


namespace Weaver;


class WeaveException extends \Exception {

    const UNKNOWN_METHOD_RETURN_TYPE = 1;
    const INTERFACE_NOT_IMPLEMENTED = 2;
    const INTERFACE_NOT_SET = 3;
    const INTERFACE_NOT_VALID = 4;
    const UNKNOWN_WEAVE_TYPE = 5;
    const IO_ERROR = 6;
    const PROPERTY_NAME_INVALID = 7;
    const METHOD_NAME_INVALID = 8;
    const DUPLICATE_METHOD = 9;
}

 
<?php


namespace Weaver;


class ClosureFactoryInfo {

    private $functionName;
    
    private $params;

    private $body;

    function __construct($functionName, $params, $body) {
        $this->functionName = $functionName;
        $this->params = $params;
        $this->body = $body;
    }

    /**
     * @return mixed
     */
    public function getFunctionName() {
        return $this->functionName;
    }

    /**
     * @return string
     */
    public function __toString() {
        $paramsString = getParamsAsString($this->params, true);
        $output = "    function ".$this->functionName."($paramsString) {" ;
        $output .= $this->body;
        $output .= "}";
        return $output;
    }
}

 
<?php


namespace Weaver;


class ClosureFactoryInfo {

    private $functionName;
    
    private $params;

    private $body;
    
    private $classname;

    function __construct($functionName, $params, $body, $classname) {
        $this->functionName = $functionName;
        $this->params = $params;
        $this->body = $body;
        $this->classname = $classname;

    }

    /**
     * @return string
     */
    public function __toString() {
        $paramsString = getParamsAsString($this->params, true);

        $output = "";
        $output = "
    /**
     * @return ".$this->classname."
     */\n";

        $output .= "    function ".$this->functionName."($paramsString) {" ;
        $output .= $this->body;
        $output .= "}";
        return $output;
    }
}

 
<?php

//Auto-generated by Weaver - https://github.com/Danack/Weaver
//
//Do not be surprised if any changes to this file are over-written.
//
namespace Example;

class ProxyWithConstantXTestClass extends \Example\TestClass
{

    const A_CONSTANT = 'constantIsSet';

    protected $queryString = null;

    public function __construct($queryString)
    {
        parent::__construct($queryString);
    }


}

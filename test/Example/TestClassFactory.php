<?php


namespace Example;


interface TestClassFactory {

    /**
     * @param $queryString
     * @return TestClass
     */
    function create($queryString);
}

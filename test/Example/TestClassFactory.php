<?php


namespace Example;


interface DBConnectionFactory {

    /**
     * @param $queryString
     * @return TestClass
     */
    function create($queryString);
}

<?php


namespace Example;


interface DBConnection {
    
    function executeQuery($params);
    
    function anotherFunction($someParameter);
} 
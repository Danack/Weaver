<?php


namespace Example;


interface DBConnection {
    function executeQuery($params);
} 
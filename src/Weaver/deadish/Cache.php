<?php


namespace Weaver;


class Cache {

    function put($key, $value) {
        apc_add($key, $value);
    }
    
    function get($name) {
        return apc_fetch($name);
    }
}

 
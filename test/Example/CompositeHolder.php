<?php


namespace Example;


class CompositeHolder {

    function renderElement() {
        return 'CompositeHolder';
    }
    
    function render() {
        return $this->renderElement();
    }
    
}

 
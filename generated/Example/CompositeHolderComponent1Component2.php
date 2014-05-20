<?php

//Auto-generated by Weaver - https://github.com/Danack/Weaver
//
//Do not be surprised if any changes to this file are over-written.
//
namespace Example;

class CompositeHolderComponent1Component2
{

    const output = 'CompositeHolder';

    private $testValue = null;

    public function construct___construct($testValue)
    {
        //added for code coverage.
                $this->testValue = $testValue;
    }

    public function __construct(\Example\Component1 $component1, \Example\Component2 $component2, $testValue)
    {
        $this->component1 = $component1;
        $this->component2 = $component2;
        		$this->construct___construct($testValue);
    }

    private function renderElementCompositeHolder()
    {
        return self::output;
    }

    public function render()
    {
        return $this->renderElement();
    }

    public function unused()
    {
        return self::output;
    }

    public function methodNotUsedInInterface()
    {
        return $this->component1->methodNotUsedInInterface();
    }

    public function renderElement()
    {
        $result = '';
            $result .= $this->component1->renderElement();
            $result .= $this->component2->renderElement();

        return $result;
    }


}

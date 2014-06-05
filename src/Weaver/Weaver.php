<?php


namespace Weaver;



class Weaver {

    /**
     * @param $sourceClass
     * @param $weaveInfo
     * @return WeaveResult
     * @throws WeaveException
     */
    static function weave($sourceClass, $weaveInfo) {
        if ($weaveInfo instanceof ImplementsWeaveInfo) {
            $weaver = new ImplementsWeaveGenerator($sourceClass, $weaveInfo);
        }
        else if ($weaveInfo instanceof CompositeWeaveInfo) {
            $weaver = new CompositeWeaveGenerator($sourceClass, $weaveInfo);
        }
        else if ($weaveInfo instanceof ExtendWeaveInfo) {
            $weaver = new ExtendWeaveGenerator($sourceClass, $weaveInfo);
        }
        else {
            throw new WeaveException("Unknown type of weaveInfo");
        }

        $result = $weaver->generate();
        return $result;
    }
}

 
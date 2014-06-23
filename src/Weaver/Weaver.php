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

        switch (true) {

            case($weaveInfo instanceof CompositeWeaveInfo): {
                $weaver = new CompositeWeaveGenerator($sourceClass, $weaveInfo);
                break;
            }
            case($weaveInfo instanceof ExtendWeaveInfo): {
                $weaver = new ExtendWeaveGenerator($sourceClass, $weaveInfo);
                break;
            }
            case($weaveInfo instanceof ImplementWeaveInfo): {
                $weaver = new ImplementWeaveGenerator($sourceClass, $weaveInfo);
                break;
            }
            case($weaveInfo instanceof LazyWeaveInfo): {
                $weaver = new LazyWeaveGenerator($sourceClass, $weaveInfo);
                break;
            }
            default: {
                throw new WeaveException(
                    "Unrecognised type of weaveInfo [" . get_class($weaveInfo) . "]",
                    WeaveException::UNKNOWN_WEAVE_TYPE
                );
            }
        }

        $result = $weaver->generate();
        return $result;
    }
}

 
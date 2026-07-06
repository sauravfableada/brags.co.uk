<?php


namespace WeDevs\DokanPro\Dependencies\Printful\Structures;


use WeDevs\DokanPro\Dependencies\Printful\Exceptions\PrintfulException;

abstract class BaseItem
{
    /**
     * Convert response array to item object
     *
     * @param array $raw
     * @return static|void
     * @throws PrintfulException
     */
    public static function fromArray(array $raw)
    {
        throw new PrintfulException(
            __CLASS__ . ' does not have fromArray() implementation. ' .
            'Data given: ' . print_r($raw, true)
        );
    }
}
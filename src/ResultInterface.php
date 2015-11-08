<?php
namespace GuzzleHttp\Command;

/**
 * @TODO description
 */
interface ResultInterface extends \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * Returns the result data from the response as an array.
     *
     * @return array
     */
    public function toArray();
}

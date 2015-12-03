<?php
namespace GuzzleHttp\Command;

/**
 * @TODO description
 */
interface ResultInterface extends \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * Returns the result's data as an array.
     *
     * @return array
     */
    public function getData();
}

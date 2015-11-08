<?php
namespace GuzzleHttp\Command;

use GuzzleHttp\HandlerStack;

/**
 * A command object encapsulates the input parameters used to control the
 * creation of a HTTP request and processing of a HTTP response.
 *
 * Using the toArray() method will return the input parameters of the command
 * as an associative array.
 */
interface CommandInterface extends \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @TODO Description
     *
     * @return HandlerStack
     */
    public function getHandlerStack();

    /**
     * Get the name of the command.
     *
     * @return string
     */
    public function getName();

    /**
     * Check if the command has a parameter by name.
     *
     * @param string $name Name of the parameter to check.
     *
     * @return bool
     */
    public function hasParam($name);

    /**
     * Returns the parameters of the command as an array.
     *
     * @return array
     */
    public function toArray();
}

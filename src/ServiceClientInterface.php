<?php
namespace GuzzleHttp\Command;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Command\Exception\CommandException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Web service client interface.
 */
interface ServiceClientInterface
{
    /**
     * Create a command for an operation name.
     *
     * Special keys may be set on the command to control how it behaves.
     * Implementations SHOULD be able to utilize the following keys or throw
     * an exception if unable.
     *
     * @param string $name   Name of the operation to use in the command
     * @param array  $args   Arguments to pass to the command
     *
     * @return CommandInterface
     * @throws \InvalidArgumentException if no command can be found by name
     */
    public function getCommand($name, array $args = []);

    /**
     * Execute a single command.
     *
     * @param CommandInterface $command Command to execute
     *
     * @return ResultInterface The result of the executed command
     * @throws CommandException
     */
    public function execute(CommandInterface $command);

    /**
     * Execute a single command asynchronously
     *
     * @param CommandInterface $command Command to execute
     *
     * @return PromiseInterface A Promise that resolves to a Result.
     */
    public function executeAsync(CommandInterface $command);

    /**
     * Executes many commands concurrently using a fixed pool size.
     *
     * Exceptions encountered while executing the commands will not be thrown.
     * Instead, callers are expected to handle errors using the event system.
     *
     *     $commands = [$client->getCommand('foo', ['baz' => 'bar'])];
     *     $client->executeAll($commands);
     *
     * @param array|\Iterator $commands Array or iterator that contains
     *     CommandInterface objects to execute.
     * @param array $options Associative array of options to apply.
     * @see GuzzleHttp\Command\ServiceClientInterface::createPool for options.
     */
    public function executeAll($commands, array $options = []);

    /**
     * Creates a future object that, when dereferenced, sends commands in
     * parallel using a fixed pool size.
     *
     * Exceptions encountered while executing the commands will not be thrown.
     * Instead, callers are expected to handle errors using the event system.
     *
     * @param array|\Iterator $commands Array or iterator that contains
     *     CommandInterface objects to execute with the client.
     * @param array $options Associative array of options to apply.
     *     - concurrency: (int) Max number of commands to send concurrently.
     *       When this number of concurrent requests are created, the sendAll
     *       function blocks until all of the futures have completed.
     *     - init: (callable) Receives an InitEvent from each command.
     *     - prepare: (callable) Receives a PrepareEvent from each command.
     *     - process: (callable) Receives a ProcessEvent from each command.
     *
     * @return PromiseInterface
     */
    public function createPool($commands, array $options = []);

    /**
     * Get the HTTP client used to send requests for the web service client
     *
     * @return ClientInterface
     */
    public function getHttpClient();

    /**
     * Get the HandlerStack which can be used to add middleware to the client.
     *
     * @return HandlerStack
     */
    public function getHandlerStack();
}

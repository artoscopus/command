<?php
namespace GuzzleHttp\Command;

use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromisorInterface;
use GuzzleHttp\Promise\EachPromise;

/**
 * Sends and iterator of commands concurrently using a capped pool size.
 *
 * The pool will read from an iterator until it is cancelled or until the
 * iterator is consumed. When a command is yielded, it is executed.
 */
class CommandPool implements PromisorInterface
{
    /** @var EachPromise */
    private $each;

    /**
     * @param ServiceClientInterface $client   Client used to send the requests.
     * @param array|\Iterator        $commands Commands to send concurrently.
     * @param array                  $config   Associative array of options:
     *     - concurrency: (int) Maximum number of commands to execute concurrently.
     *     - fulfilled: (callable) Function to invoke when a command completes.
     *     - rejected: (callable) Function to invoke when a command fails.
     */
    public function __construct(
        ServiceClientInterface $client,
        $commands,
        array $config = []
    ) {
        if (!isset($config['concurrency'])) {
            $config['concurrency'] = 25;
        }

        $iterable = Promise\iter_for($commands);
        $commands = function () use ($iterable, $client) {
            foreach ($iterable as $command) {
                if (!$command instanceof CommandInterface) {
                    throw new \InvalidArgumentException('The iterator must '
                        . 'yield instances of ' . CommandInterface::class);
                }
                yield $client->executeAsync($command);
            }
        };

        $this->each = new EachPromise($commands(), $config);
    }

    public function promise()
    {
        return $this->each->promise();
    }

    /**
     * Sends multiple commands concurrently and returns an array of results
     * and exceptions that uses the same ordering as the provided commands.
     *
     * IMPORTANT: This method keeps every command and result in memory, and
     * as such, is NOT recommended when executing a large number or an
     * indeterminate number of commands concurrently.
     *
     * @param ServiceClientInterface $client   Client used to send the requests.
     * @param array|\Iterator        $commands Commands to send concurrently.
     * @param array                  $config   Passes through the config in
     *                                         {@see GuzzleHttp\Pool::__construct}
     *
     * @return array Returns an array containing the response or an exception
     *               in the same order that the requests were sent.
     * @throws \InvalidArgumentException if the event format is incorrect.
     */
    public static function batch(
        ServiceClientInterface $client,
        $commands,
        array $config = []
    ) {
        $results = [];
        self::composeCallback($config, 'fulfilled', $results);
        self::composeCallback($config, 'rejected', $results);
        (new static($client, $commands, $config))->promise()->wait();
        ksort($results);

        return $results;
    }

    private static function composeCallback(array &$config, $name, array &$results)
    {
        if (!isset($config[$name])) {
            $config[$name] = function ($v, $k) use (&$results) {
                $results[$k] = $v;
            };
        } else {
            $currentFn = $config[$name];
            $config[$name] = function ($v, $k) use (&$results, $currentFn) {
                $currentFn($v, $k);
                $results[$k] = $v;
            };
        }
    }
}

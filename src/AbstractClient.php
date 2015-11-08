<?php
namespace GuzzleHttp\Command;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use GuzzleHttp\Command\Exception\CommandException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Abstract service client implementation.
 *
 * Provides a basic implementation of several methods. Concrete implementations
 * may choose to extend this class or to completely implement all of the methods
 * of ServiceClientInterface.
 */
abstract class AbstractClient implements ServiceClientInterface
{
    /** @var HttpClientInterface HTTP client used to send requests */
    private $httpClient;

    /** @var HandlerStack */
    private $handlerStack;

    /**
     * @param HttpClientInterface $httpClient
     * @param HandlerStack        $handlerStack
     */
    public function __construct(
        HttpClientInterface $httpClient = null,
        HandlerStack $handlerStack = null
    ) {
        $this->httpClient = $httpClient ?: new HttpClient();
        $this->handlerStack = $handlerStack ?: new HandlerStack();
        $this->handlerStack->setHandler($this->createCommandHandler());
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    public function getHandlerStack()
    {
        return $this->handlerStack;
    }

    public function getCommand($name, array $params = [])
    {
        return new Command($name, $params, clone $this->handlerStack);
    }

    /**
     * Creates and executes a command for an operation by name.
     *
     * @param string $name Name of the command to execute.
     * @param array $args Arguments to pass to the getCommand method.
     *
     * @return ResultInterface|PromiseInterface
     * @throws \Exception
     * @see \GuzzleHttp\Command\ServiceClientInterface::getCommand
     */
    public function __call($name, array $args)
    {
        $args = isset($args[0]) ? $args[0] : [];
        if (substr($name, -5) === 'Async') {
            $command = $this->getCommand(substr($name, 0, -5), $args);
            return $this->executeAsync($command);
        } else {
            return $this->execute($this->getCommand($name, $args));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function execute(CommandInterface $command)
    {
        return $this->executeAsync($command)->wait();
    }

    /**
     * {@inheritdoc}
     */
    public function executeAsync(CommandInterface $command)
    {
        $stack = $command->getHandlerStack() ?: $this->handlerStack;
        $handler = $stack->resolve();

        return $handler($command);
    }

    /**
     * {@inheritdoc}
     */
    public function executeAll($commands, array $options = [])
    {
        $this->createPool($commands, $options)->promise()->wait();
    }

    /**
     * {@inheritdoc}
     */
    public function createPool($commands, array $options = [])
    {
        return new CommandPool($this, $commands, $options);
    }

    /**
     * Prepares a Request from a Command.
     *
     * @param CommandInterface $command
     *
     * @return RequestInterface
     */
    abstract protected function serializeRequest(CommandInterface $command);

    /**
     * Prepares a Result from a Response.
     *
     * @param ResponseInterface $response
     *
     * @return ResultInterface
     */
    abstract protected function unserializeResponse(ResponseInterface $response);

    /**
     * Defines the main handler for commands that uses the HTTP client.
     *
     * @return callable
     */
    private function createCommandHandler()
    {
        return function (CommandInterface $command) {
            return Promise\coroutine(function () use ($command) {
                // Get the HTTP options.
                $opts = $command['@http'] ?: [];
                unset($command['@http']);

                try {
                    // Prepare the request from the command and send it.
                    $request = $this->serializeRequest($command);
                    $promise = $this->httpClient->sendAsync($request, $opts);
                    /** @var ResponseInterface $response */
                    $response = (yield $promise);

                    // Create a result from the response, and include some meta
                    // information about the request/response under the @http key.
                    $result = $this->unserializeResponse($response);
                    $result['@http'] = [
                        'statusCode'   => $response->getStatusCode(),
                        'effectiveUri' => (string) $request->getUri(),
                        'headers'      => $response->getHeaders(),
                    ];

                    yield $result;
                } catch (\Exception $e) {
                    throw CommandException::create($command, $e);
                }
            });
        };
    }
}

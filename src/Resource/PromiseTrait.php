<?php

namespace Phediverse\MastodonRest\Resource;

use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise\PromiseInterface;
use Phediverse\MastodonRest\Client;
use Phediverse\MastodonRest\ErrorCodes;
use Phediverse\MastodonRest\Exception\ResolveException;
use Psr\Http\Message\ResponseInterface;

trait PromiseTrait
{
    /** @var PromiseInterface|true|null set to true if resolved */
    protected $promise;

    /** @var Client */
    protected $client;

    /**
     * @param array $data
     * @param Client|null $client
     * @return static
     */
    public static function fromData(array $data, Client $client = null)
    {
        $me = (new static($client));
        $me->promise = true;
        return $me->hydrate($data);
    }

    /**
     * @param PromiseInterface $promise
     * @param Client|null $client
     * @return static
     */
    public static function fromPromise(PromiseInterface $promise, Client $client = null)
    {
        $me = new static($client);
        $me->promise = $promise;
        return $me;
    }

    /**
     * @param ResponseInterface $response
     * @param Client|null $client
     * @return static
     */
    public static function fromResponse(ResponseInterface $response, Client $client = null)
    {
        $me = new static($client);
        return $me->resolve($response);
    }

    protected function __construct(Client $client = null)
    {
        $this->client = $client;
    }

    /**
     * @param Client $client
     * @return $this
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @param ResponseInterface|null $response
     * @return $this
     */
    protected function resolve(ResponseInterface $response = null)
    {
        if ($this->promise === true) {
            return $this;
        }

        if (!$response) {
            if (!$this->promise) {
                throw new \LogicException("Missing response and promise", ErrorCodes::NO_PROMISE_OR_RESPONSE);
            }

            try {
                $response = $this->promise->wait();
            } catch (TransferException $e) {
                throw new ResolveException("HTTP error: " . get_class($e), $e->getCode(), $e);
            }
        }

        if ($response->getStatusCode() < 200 && $response->getStatusCode() >= 300) {
            throw new ResolveException("Error response passed to " . __CLASS__ . "::fromResponse()",
                $response->getStatusCode());
        }
        if (!($json = json_decode($response->getBody()->getContents(), JSON_OBJECT_AS_ARRAY))) {
            throw new ResolveException("Response was not valid JSON", ErrorCodes::INVALID_JSON);
        }

        $this->promise = true;
        return $this->hydrate($json);
    }

    protected function getClient() : Client
    {
        if (!$this->client) {
            throw new \InvalidArgumentException("This method is not available without a client");
        }
    }

    /**
     * @param array $data
     * @return $this
     */
    abstract protected function hydrate(array $data);
}

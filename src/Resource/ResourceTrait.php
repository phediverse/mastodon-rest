<?php

namespace Phediverse\MastodonRest\Resource;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Phediverse\MastodonRest\Client;

trait ResourceTrait
{
    use PromiseTrait;

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

    protected function getClient() : Client
    {
        if (!$this->client) {
            throw new \InvalidArgumentException("This method is not available without a client");
        }
        return $this->client;
    }
}

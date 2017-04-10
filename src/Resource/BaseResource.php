<?php

namespace Phediverse\MastodonRest\Resource;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Phediverse\MastodonRest\Client;

abstract class BaseResource implements \Serializable, \JsonSerializable
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

    // serialization

    public function jsonSerialize()
    {
        $arr = $this->resolve()->toArray();

        unset($arr['client']);
        unset($arr['promise']);

        $arr['resourceType'] = get_class($this);

        return $arr;
    }

    abstract protected function toArray() : array;

    public function serialize()
    {
        $data = $this->jsonSerialize();
        unset($data['resourceType']);
        return json_encode($data);
    }

    public function unserialize($serialized)
    {
        $this->promise = true;
        $this->hydrate(json_decode($serialized, JSON_OBJECT_AS_ARRAY));
    }
}

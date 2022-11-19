<?php

namespace Phediverse\MastodonRest\Resource;

use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise\PromiseInterface;
use Phediverse\MastodonRest\ErrorCodes;
use Phediverse\MastodonRest\Exception\ResolveException;
use Psr\Http\Message\ResponseInterface;

trait PromiseTrait
{
    /** @var PromiseInterface|true|null set to true if resolved */
    protected $promise;

    /**
     * @param ResponseInterface|null $response
     * @return $this
     */
    public function resolve(ResponseInterface $response = null)
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

    /**
     * @param array $data
     * @return $this
     */
    abstract public function hydrate(array $data);
}

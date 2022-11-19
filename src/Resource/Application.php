<?php

namespace Phediverse\MastodonRest\Resource;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

class Application implements \JsonSerializable
{
    use PromiseTrait;

    const REDIRECT_NONE = 'urn:ietf:wg:oauth:2.0:oob';

    protected $clientId;
    protected $clientSecret;

    protected $name;
    protected $redirectUri;
    protected $defaultScopes;
    protected $instanceHost;

    public static function fromPromise(
        PromiseInterface $promise,
        string $name,
        string $redirectUri,
        array $scopes,
        string $instanceHost
    ) {
        $me = new self($name, $redirectUri, $scopes, $instanceHost);
        $me->promise = $promise;
        return $me;
    }

    public static function fromResponse(
        ResponseInterface $response,
        string $name,
        string $redirectUri,
        array $scopes,
        string $instanceHost
    ) {
        $me = new self($name, $redirectUri, $scopes, $instanceHost);
        return $me->resolve($response);
    }

    public static function fromJsonConfig(string $jsonConfig)
    {
        return static::fromData(json_decode($jsonConfig, JSON_OBJECT_AS_ARRAY));
    }

    public static function fromData(array $data)
    {
        foreach (['client_id', 'client_secret', 'name', 'redirect_uris', 'scopes', 'host'] as $key) {
            if (!isset($data[$key]) || !$data[$key]) {
                throw new \InvalidArgumentException("Missing field: " . $key);
            }
        }

        $me = new self($data['name'], $data['redirect_uris'], explode(' ', $data['scopes']), $data['host']);
        $me->promise = true;
        return $me->hydrate($data);
    }

    public function getDefaultScopes(): array
    {
        return $this->defaultScopes;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function getAuthHeader(): string
    {
        return 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret);
    }

    public function getInstanceHost()
    {
        return $this->instanceHost;
    }

    public function jsonSerialize()
    {
        $this->resolve();

        return [
            'name' => $this->name,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uris' => $this->redirectUri,
            'scopes' => implode(' ', $this->defaultScopes),
            'host' => $this->instanceHost
        ];
    }

    protected function __construct(string $name, string $redirectUri, array $scopes, string $instanceHost)
    {
        $this->name = $name;
        $this->redirectUri = $redirectUri;
        $this->defaultScopes = $scopes;
        $this->instanceHost = $instanceHost;
    }

    public function hydrate(array $data) : self
    {
        $this->clientId = $data['client_id'];
        $this->clientSecret = $data['client_secret'];
        return $this;
    }
}

<?php

namespace Phediverse\MastodonRest;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Phediverse\MastodonRest\Resource\Instance;

class Client
{
    const API_BASE = '/api/v1/';

    public static function build(string $instanceHostname, string $accessToken)
    {
        if (substr($instanceHostname, 0, 4) !== 'http') {
            $instanceHostname = 'https://' . $instanceHostname;
        }

        return new static(new \GuzzleHttp\Client([
            'base_uri' => $instanceHostname . static::API_BASE,
            'headers' => ['Authorization' => 'Bearer ' . $accessToken]
        ]));
    }

    protected $http;

    public function __construct(ClientInterface $http)
    {
        $this->http = $http;
    }

    public function getInstanceInfo(string $host = null) : Instance
    {
        if ($host !== null) {
            $host = (substr($host, 0, 4) !== 'http' ? ('https://' . $host) : $host) . static::API_BASE;
        }

        return $this->get(($host ?: '') . 'instance', Instance::class, ['headers' => []]);
    }

    protected function get(string $uri, string $class, array $httpOptions = [])
    {
        /** @noinspection PhpUndefinedMethodInspection */ // too much dynamic magic for PhpStorm to handle
        return $class::fromPromise($this->http->sendAsync(new Request('GET', $uri, $httpOptions)));
    }
}

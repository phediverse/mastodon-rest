<?php

namespace Phediverse\MastodonRest\Auth;

use GuzzleHttp\Psr7\Request;
use Phediverse\MastodonRest\Resource\Application;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

class AppRegisterClient
{
    public static function forInstance(string $instanceHostname)
    {
        if (substr($instanceHostname, 0, 4) !== 'http') {
            $instanceHostname = 'https://' . $instanceHostname;
        }

        return new static(new Client(['base_uri' => $instanceHostname]));
    }

    protected $http;

    public function __construct(ClientInterface $http)
    {
        $this->http = $http;
    }

    public function createApp(string $name, string $redirectUri, array $scopes = Scope::ALL) : Application
    {
        return Application::fromPromise($this->http->sendAsync(new Request('POST', '/api/v1/apps', [
            'Content-Type' => 'application/json'
        ], json_encode([
            'client_name' => $name,
            'redirect_uris' => $redirectUri,
            'scopes' => implode(' ', $scopes)
        ]))), $name, $redirectUri, $scopes, $this->http->getConfig('base_uri'));
    }
}

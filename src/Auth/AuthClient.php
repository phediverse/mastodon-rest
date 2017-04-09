<?php

namespace Phediverse\MastodonRest\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Phediverse\MastodonRest\ErrorCodes;
use Phediverse\MastodonRest\Resource\Application;
use GuzzleHttp\ClientInterface;

class AuthClient
{
    public static function forApplication(Application $app)
    {
        return new static(new Client([
            'base_uri' => $app->getInstanceHost(),
            'headers' => ['Authorization' => $app->getAuthHeader()]
        ]), $app->getClientId(), $app->getRedirectUri(), $app->getDefaultScopes());
    }

    protected $http;
    protected $clientId;
    protected $redirectUri;
    protected $defaultScopes;

    public function __construct(ClientInterface $http, string $clientId, string $redirectUri, array $defaultScopes)
    {
        $this->http = $http;
        $this->clientId = $clientId;
        $this->redirectUri = $redirectUri;
        $this->defaultScopes = $defaultScopes;
    }

    public function getAuthCodeUrl(string $state = null, array $scopes = null) : string
    {
        return $this->http->getConfig('base_uri') . '/oauth/authorize?' . http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scopes' => implode(' ', $scopes ?: $this->defaultScopes),
            'state' => $state
        ]);
    }

    public function finishAuthCodeRequest(string $code) : string
    {
        return $this->getToken([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri
        ]);
    }

    public function login(string $email, string $password, array $scopes = null) : string
    {
        return $this->getToken([
            'grant_type' => 'password',
            'username' => $email,
            'password' => $password,
            'scope' => implode(' ', $scopes ?: $this->defaultScopes)
        ]);
    }

    protected function getToken(array $params) : string
    {
        $response = $this->http->send(new Request('POST', '/oauth/token', [
            'Content-type' => 'application/x-www-form-urlencoded'
        ], http_build_query($params)));

        if (!($json = json_decode($response->getBody()->getContents(), JSON_OBJECT_AS_ARRAY)) ||
                !isset($json['access_token'])) {
            throw new \RuntimeException('Response body was not valid JSON, or did not include an access token.',
                ErrorCodes::INVALID_JSON);
        }

        return $json['access_token'];
    }
}

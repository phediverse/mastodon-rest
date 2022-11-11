<?php

namespace Phediverse\MastodonRest;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Phediverse\MastodonRest\Resource\Account;
use Phediverse\MastodonRest\Resource\BaseResource;
use Phediverse\MastodonRest\Resource\Instance;

class Client
{
    const API_BASE = '/api/v1/';
    const RESOURCE_CLASSES = [Instance::class, Account::class]; // used for more secure PHP deserialization

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
    protected $instanceHostname;
    protected $cache;

    /**
     * @param ClientInterface $http
     * @param bool $useCache
     */
    public function __construct(ClientInterface $http, bool $useCache = true)
    {
        $this->http = $http;
        $this->instanceHostname = parse_url($http->getConfig('base_uri'), PHP_URL_HOST);
        $this->cache = $useCache ? [] : null;
    }

    /////////////////////////
    ///                   ///
    ///     RESOURCES     ///
    ///                   ///
    /////////////////////////

    /**
     * @param string $serialized PHP or JSON serialized data
     * @param string|null $uri if supplied, adds to the cache at that location; should generally a relative URI
     * @return BaseResource the original resource, as an object, with this client attached
     */
    public function deserialize(string $serialized, string $uri = null)
    {
        switch ($serialized[0] ?? '') {
            case 'C': // PHP serialization
                $resource = unserialize($serialized, static::RESOURCE_CLASSES);
                if (!($resource instanceof BaseResource)) {
                    throw new \InvalidArgumentException("Serialized data is not a resource");
                }
                $resource->setClient($this);
                break;
            case '{': // JSON serialization
                $decoded = json_decode($serialized, JSON_OBJECT_AS_ARRAY);
                if (!is_subclass_of($class = ($decoded['resourceType'] ?? 'INVALID'), BaseResource::class)) {
                    throw new \InvalidArgumentException("Serialized data is not a resource");
                }

                /** @noinspection PhpUndefinedMethodInspection */ // too much dynamic magic for PhpStorm to handle
                $resource = $class::fromData($decoded, $this);
                break;
            default:
                throw new \InvalidArgumentException("Unknown serialization format");
        }

        if ($uri && is_array($this->cache)) { // add to cache if we have a cache and a URI is provided
            if (!isset($class)) {
                $class = get_class($resource);
            }

            if (!isset($this->cache[$class])) {
                $this->cache[$class] = [];
            }
            $this->cache[$class][$uri] = $resource;
        }

        return $resource;
    }

    // Instances

    public function getInstanceHostname() : string
    {
        return $this->instanceHostname;
    }

    public function getInstance(string $host = null, bool $useCache = true) : Instance
    {
        if ($host !== null) {
            $host = (substr($host, 0, 4) !== 'http' ? ('https://' . $host) : $host) . static::API_BASE;
        }

        return $this->get(($host ?: '') . 'instance', Instance::class, $useCache, ['headers' => []]);
    }

    // Accounts

    public function getAccountId() : int
    {
        return $this->getAccount()->getId();
    }

    public function getAccount(int $id = null, bool $useCache = true) : Account
    {
        return $this->get('accounts/' . ($id ?: 'verify_credentials'), Account::class, $useCache);
    }

    /**
     * Get the list of accounts followed by the given account id
     * @param int|Account|null $string the account id. When null, it will use the default account id
     * @return Account[]
     */
    public function getFollowings($id  =null, bool $useCache = true) : array {
        if($id instanceof Account) {
            $id = $id->getId();
        }
        $uri = 'accounts/' . ($id ?: $this->getAccountId()) . '/following';
        $class = Account::class . "[]";
        return $this->get($uri, $class, $useCache);
    }

    public function getFollowers($id  =null, bool $useCache = true) : array {
        if($id instanceof Account) {
            $id = $id->getId();
        }
        $uri = 'accounts/' . ($id ?: $this->getAccountId()) . '/followers';
        $class = Account::class . "[]";
        return $this->get($uri, $class, $useCache);
    }

    /////////////////////////
    ///                   ///
    ///   CLIENT TOOLS    ///
    ///                   ///
    /////////////////////////

    // cache methods

    /**
     * Clears the internal object cache.
     *
     * @param string|null $class If null, clear everything. Otherwise, clear only entries for a specific class.
     * @param string|null $uri If non-null AND $class is set, clear only the specified key in the class.
     * @return $this
     */
    public function clearCache(string $class = null, string $uri = null)
    {
        if ($this->cache) {
            if (!$class) {
                $this->cache = [];
            } elseif (!$uri) {
                $this->cache[$class] = [];
            } else {
                unset($this->cache[$class][$uri]);
            }
        }

        return $this;
    }

    /**
     * Returns the resource cache of this client.
     *
     * @param bool $resolve whether to wait until all items in the cache have their promises resolved before returning
     * @return array
     */
    public function getCacheContents(bool $resolve = true)
    {
        if (!is_array($this->cache)) {
            throw new \InvalidArgumentException("Cache is turned off for this client");
        }

        if ($resolve) {
            foreach ($this->cache as &$class) {
                foreach ($class as &$entry) {
                    if (method_exists($entry, 'resolve')) {
                        $entry->resolve();
                    }
                }
            }
        }

        return $this->cache;
    }

    // under-the-hood methods

    /**
     * Perform the http request, using the cache if needed and with the correct options
     * @param string $uri the uri we want to query
     * @param string $class the class to rehydrate
     * @param bool $useCache set to true if we want to use the cache
     * @param array $httpOptions the options used to send the request
     * @return an object of the $class
     */
    protected function get(string $uri, string $class, bool $useCache = true, array $httpOptions = [])
    {
        if ($useCache && is_array($this->cache) && isset($this->cache[$class][$uri])) {
            return $this->cache[$class][$uri];
        }

        $item = null;
        if (str_ends_with($class, "[]")) {
            $class = substr($class, 0, strpos($class, "[]"));
            $array_query = true;
            // Run query with correct pagination parameters and aggregate all results
            $jsonResponse = [];
            // in a funny way, Mastodon sends navigation links not in json response, but as http headers
            // So we need to have the http library return that
            while ($uri!=null) {
                // I don't yet understand how to run request async and analyze headers afterwards
                $response  = $this->http->request('GET', $uri, $httpOptions);
                $body = json_decode($response->getBody(), true);
                $jsonResponse = array_merge($jsonResponse, $body);
                $uri = $this->getNextPage($response);
            }
            // Now all paginated queries are run, let's hydrate objects
            $item = [];
            foreach ($jsonResponse as $index => $jsonObject) {
                array_push($item, $class::fromData($jsonObject));
            }
        } else {
            /** @noinspection PhpUndefinedMethodInspection */ // too much dynamic magic for PhpStorm to handle
            $item = $class::fromPromise($this->http->sendAsync(new Request('GET', $uri, $httpOptions)));
        }

        if ($useCache && is_array($this->cache)) {
            if (!isset($this->cache[$class])) {
                $this->cache[$class] = [];
            }
            $this->cache[$class][$uri] = $item;
        }

        return $item;
    }

    protected function getNextPage($response) : ?string {
        $linksHeader = $response->getHeader("Link");
        $linksLine = $linksHeader[0];
        $parsedLinks = \GuzzleHttp\Psr7\Header::parse($linksLine);
        $linksDict = [];
        // The +1 is here to remove the '/' after the prefix
        $prefix = strlen($this->http->getConfig('base_uri'))+1;
        foreach ($parsedLinks as $index => $link) {
            // Don't forget that Guzzle HTTP client has a concept of base path that should be removed from the given urls
            $linksDict[$link['rel']] = substr($link[0], $prefix, -1);
        }
        if(array_key_exists('next', $linksDict)) {
            return $linksDict['next'];
        } else {
            return null;
        }
    }
}

Phediverse Mastodon REST Client
===============================

This is a PHP HTTP client, using the Guzzle HTTP library, to the Mastodon federated social network REST API.

Installation
------------

You'll need PHP 7.0+ and Composer to get this running. Got 'em? Run:

```bash
composer require phediverse/mastodon-rest
```

to import the library into your project. Then, wherever you use it, make sure you've included Composer's autoloader, via

```php
require __DIR__ . 'vendor/autoload.php'; // assuming you're in the same directory as the vendor dir Composer created
```

Getting Authenticated
---------------------

With a few exceptions (see **Unauthenticated Endpoints** below), you need an access token, generated for a valid user
from a valid registered application on the instance you're pointing at, to do anything useful with the API. Both the
application and access tokens are long-lived at this point; once you get one you don't need to keep going back for a
given user. As such, different classes handle auth-related tasks than actual endpoint activity.

### Registering an App

First, you need to register an application. You'll need a name and a redirect URI, and you'll need to decide what scopes
the application should default to. Information on scopes is available at
https://github.com/tootsuite/mastodon/blob/master/docs/Using-the-API/OAuth-details.md.

Once you have your app, you can json_encode() the object to store its credentials for later use.

```php
use Phediverse\MastodonRest\{Auth\AppRegisterClient, Auth\Scope, Resource\Application};

$registerClient = AppRegisterClient::forInstance('social.targaryen.house');
$app = $registerClient->createApp('My Phediverse Reader', 'https://example.com/oauth_landing', [Scope::READ]);
```

If you're planning to use the application with logins directly (rather than the proper OAuth 2 way via an Authorization
Code grant), or you're okay with folks copy-pasting an Auth Code into your app from their browser, you can use the
`Resource\Application::REDIRECT_NONE` URI rather than a standard HTTP URL. Additionally, if you don't specify a scope on
app create, the client will automatically ask for all three scopes for you, aka `Scope::ALL`. Or, to put it another way:

```php
$anotherApp = $registerClient->createApp('No Redirect, All The Power', Application::REDIRECT_NONE);
file_put_contents('instance_creds.json', json_encode($anotherApp));
```

### Logging In Via Authorization Code Grant

**NOTE:** The examples below use built-in PHP functions for things like request handling; if you're using this library
within a (micro-)framework, use that framework's request/response handling methods instead.

#### Starting The Process

Now that you've got your app, you can go through the OAuth2 Authorization Code grant process. To start, let's pull our
app out of its configuration JSON, and drop it into an AuthClient instance.

```php
$app = \Phediverse\MastodonRest\Resource\Application::fromJson(file_get_contents('instance_creds.json'));
$authClient = \Phediverse\MastodonRest\Auth\AuthClient::forApplication($app);
```

Then let's figure out where to redirect our users, adding in a random `state` value to avoid cross-site request
forgery (CSRF) attacks. Mastodon will hand us back `state` as a query string parameter when it redirects back to us.

```php
$url = $authClient->getAuthCodeUrl($state = bin2hex(random_bytes(12)));
// record $state to the user's session or similar
header('Location', $url); // redirect the user to the OAuth2 Auth Code URL
```

Note that, while `state` is recommended by the OAuth2 RFC, it's not necessary, and it's pointless if you don't have a
real redirect in place. In that case, you can omit that parameter in the call, or set it to null if you need to use the
second parameter: an array of requested scopes for the access token. If you leave off the second parameter, as we did
above, you'll get whatever scopes you asked for when you originally set up the app. This is a bit different than the
raw API behavior when you leave off scopes, which gives you back a token with only `read` access.

#### Finishing The Process

Once the user has signed into the Mastodon instance and allowed your app access, they'll be redirected back to you
with `code` in the query string, as well as `state` if you provided it above. If your app used the not-a-redirect,
the user will get the authorization code in heir browser window instead.

Assuming that you have a redirect landing, there's one more step to get the access token once they land on your page:

```php
// since we're in a different request than where we started this process...
$app = \Phediverse\MastodonRest\Resource\Application::fromJson(file_get_contents('instance_creds.json'));
$authClient = \Phediverse\MastodonRest\Auth\AuthClient::forApplication($app);

// verify state here; it'll be in $_GET['state']

$accessToken = $authClient->finishAuthCodeRequest($_GET['code']); // string
```

Congratulations! You now have an access token for that user for that Mastodon instance, which you can use with the main
API client class...or any other Mastodon API client, for that matter.

### Logging In Via Password Grant

If you're using this library for personal use, and as such don't mind user credentials passing through, or being stored
in, your system, the flow's a bit simpler, and doesn't have to happen across multiple requests:

```php
// still need your app and an AuthClient
$app = \Phediverse\MastodonRest\Resource\Application::fromJson(file_get_contents('instance_creds.json'));
$authClient = \Phediverse\MastodonRest\Auth\AuthClient::forApplication($app);

$accessToken = $authClient->login('your@email.address', 'SuperSecretP4$$w0rd'/*, override scopes here */);
```

As with the Authorization Code flow, if you don't specify any scopes, you'll get a token that uses the default scopes
that you specified when you set up your app.

Methods And Resources
---------------------

Now that you've got your access token, you can set up the main API client instance:

```php
$client = \Phediverse\MastodonRest\Client::build('social.targaryen.house', $accessToken);
```

Let's start by getting your own account's display name:

```php
$account = $client->getAccount(/* defaults to your ID; can put someone else's ID in here too */);
echo $account->getDisplayName(); // Your Display Name
```

Resources can also reference other resources directly, e.g.

```php
$instance = $account->getInstance(); // Instance resource
```

You can also serialize a resource, to either JSON or PHP (which uses a tweaked version of the JSON representation
under the hood), and use the client object to bring the resource back from its serialized form. note that
serializing a resource will force it to finish downloading (see **The Turbo Button** for more info on that). The
client will inject itself on deserialization, so pulling related resources will work at that point.

I also try to follow the original API response format pretty closely on the JSON side, so JSON-encoding a resource
will get you something very similar (same key names etc.) to what the Mastodon API spits out.

```php
$serializedAccount = json_encode($accout);

$deserializedAccount = $client->deserialize($serializedAccount);
echo $deserializedAccount->getId(); // your ID
```

Unauthenticated Endpoints
-------------------------

Unauthenticated endpoints don't use an access token, but they're few and far between. The main one of interest is the
instances endpoint, which we demo here. It uses the technique described in **The Turbo Button** to get instance
information significantly faster than you would by doing all the calls at once.

```php
$client = \Phediverse\MastodonRest\Client::build('mastodon.network', 'ACCESS_TOKEN');
$hosts = ['mastodon.xyz', 'icosahedron.website', 'sealion.club', 'cybre.space', 'toot.cat', 'toot.cafe'];

/** @var \Phediverse\MastodonRest\Resource\Instance[] $instances */
$instances = array_map(function($host) use ($client) { // requests are started here, in parallel!
    return $client->getInstance($host);
}, array_combine($hosts, $hosts));

foreach ($instances as $hostname => $instance) { // everything in this loop will finish around the same time
    echo $hostname . ($instance->isMastodon() ? (': ' . $instance->getName()) : ' is not a Mastodon instance') . "\n";
}

// we can also pull the name for the host we specified in client setup, as it's the default
echo "Default client: " . $client->getInstance()->getName() . "\n";
```

The Turbo Button
----------------

### Parallel Requests

Under the hood, the client tries to block as little as possible, only forcing resolution of an HTTP request (well, 
HTTPS...that's the default if you don't specify scheme in a hostname) when you ask for something that requires an
HTTP response. This may not seem like a big deal, but it means that the client will parallelize requests as much as
it can if it knows about them early enough. So ask for resource objects early, and pull info out of those resource
objects as late as possible if you have other stuff to do in your app, and things will magically go faster!

I may tweak things further to allow for more direct manipulation of the underlying promises so you can let the system
know when a bunch of requests don't necessarily need to resolve in order, for even more speed. But that's for another
day.

Ff you want to force a resource block until fully downloaded, call its `resolve()` method. That method can be chained.

A few catches: 

1. The login endpoints, whether for completing an Auth Code grant or for logging in via a username and password,
don't do anything asynchronous, since you're getting an access token back. If folks want to async-ify that bit, that
can be a task for a later date.
2. Async runs one level deep in most cases at this point; if you request a related resource before the original resource
loads, you'll block until the original resource load completes. I (or some other contributor) will fix that bit later.

### Caching

The client caches GET requests, including referencing the same resource if multiple requests for the same one are in
flight, to avoid naive use from pounding the server on the other end. If you need to clear the cache for any reason,
use `$client->clearCache()` for that; see the code for information on arguments. You can also bypass the cache for a
request by setting the `$useCache` parameter on `Client` calls to `false`.

Once I add methods for updating a given resource, updates will automatically refresh or remove the associated resource
in the cache, depending on what can be done without making another request to the server.

Contributing
------------

Feel free to fork/PR on this, though I may be nitpicky about either code style (this follows PSR-1, PSR-2, PSR-4, and
PSR-7 standards) or performance-related lazy loading concerns (when in doubt, look at how the existing code
lazy-loads all the things).

Tests will be built in due time; the use of Guzzle's ClientInterface to construct this library's Client class is
largely to aid in mocking up Mastodon's API so we can unit-test the client without instance availability.

Code is licensed MIT. I am not affiliated with the main Mastodon dev team in any meaningful way.

You can drop me an email at iansltx@gmail.com (and that's how you should report security issues...**NOT** on the public
issue tracker...public issue tracker's fine for other library-related issues though), or find me on the Fediverse at
@iansltx@social.targaryen.house.

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

Getting Started
---------------

At the moment, no authenticated endpoints have been built out, nor has support for pulling tokens from OAuth. While you
wait for that, check the below out. It uses the technique described in *The Turbo Button* to get instance information
significantly faster than you would by doing all the calls at once.

```php
// in this particular example, while both fields are required, neither are used
$client = \Phediverse\MastodonRest\Client::build('mastodon.network', 'ACCESS_TOKEN');
$hosts = ['mastodon.xyz', 'social.targaryen.house', 'sealion.club', 'cybre.space', 'toot.cat', 'toot.cafe'];

/** @var \Phediverse\MastodonRest\Resource\Instance[] $instances */
$instances = array_map(function($host) use ($client) { // requests are started here, in parallel!
    return $client->getInstanceInfo($host);
}, array_combine($hosts, $hosts));

foreach ($instances as $hostname => $instance) { // everything in this loop will finish around the same time
    echo $hostname . ($instance->isMastodon() ? (': ' . $instance->getName()) : ' is not a Mastodon instance') . "\n";
}
```

The Turbo Button
----------------

Under the hood, the client tries to block as little as possible, only forcing resolution of an HTTP request (well, 
HTTPS...that's the default if you don't specify scheme in a hostname) when you ask for something that requires an
HTTP response. This may not seem like a big deal, but it means that the client will parallelize requests as much as
it can if it knows about them early enough. So ask for resource objects early, and pull info out of those resource
objects as late as possible if you have other stuff to do in your app, and things will magically go faster!

I may tweak things further to allow for more direct manipulation of the underlying promises so you can let the system
know when a bunch of requests don't necessarily need to resolve in order, for even more speed. But that's for another
day.

Contributing
------------

Feel free to fork/PR on this, though I may be nitpicky about either code style (this follows PSR-1, PSR-2, PSR-4, and
PSR-7 standards) or performance-related lazy loading concerns (when in doubt, look at how the existing code
lazy-loads all the things).

Tests will be built in due time; the use of Guzzle's ClientInterface to construct this library's Client class is
largely to aid in mocking up Mastodon's API so we can unit-test the client without instance availability.

Code is licensed MIT. I am not affiliated with the main Mastodon dev team in any meaningful way.

You can drop me an email at iansltx@gmail.com (and that's how you should report security issues...*NOT* on the public
issue tracker...public issue tracker's fine for other library-related issues though), or find me on the Fediverse at
@iansltx@social.targaryen.house.

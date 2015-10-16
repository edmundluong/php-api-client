# PHP API Client

[![Build Status](https://travis-ci.org/edmundluong/php-api-client.svg)](https://travis-ci.org/edmundluong/php-api-client)
[![License](https://poser.pugx.org/edmundluong/php-api-client/license)](https://packagist.org/packages/edmundluong/php-api-client)

Provides a simple layer of abstraction over the [Guzzle Services](https://github.com/guzzle/guzzle-services) 
client in order to facilitate common tasks related to making API requests to web services,
such as logging and HTTP request signing for authentication purposes.

## Installation

PHP API Client requires PHP 5.5 or higher. Install the package via [Composer](http://getcomposer.org/):
```
composer require "edmundluong/php-api-client"
```

## Basic Usage

Define the API endpoints you want to access by extending the `AbstractApiDescription` base class
and overriding the `load()` method to return a `new static` with the API endpoints defined.

[See documentation on Guzzle Service Descriptions for more information.]
(http://guzzle3.readthedocs.org/webservice-client/guzzle-service-descriptions.html)

An example of a Twitter URLs API description:

```php
<?php

use Edmund\PhpApiClient\AbstractApiDescription;

class TwitterUrlsApiDescription extends AbstractApiDescription
{
    public function load()
    {
        return new static([
            'additionalProperties' => true,
            'baseUrl'              => 'http://urls.api.twitter.com/1/urls/',
            'operations'           => [
                'count' => [
                    'httpMethod'    => 'GET',
                    'uri'           => 'count.json',
                    'responseModel' => 'JsonResponse',
                    'parameters'    => [
                        'url' => [
                            'type'     => 'string',
                            'location' => 'query',
                            'required' => true
                        ]
                    ]
                ]
            ],
            'models'               => [
                'JsonResponse' => [
                    'type'                 => 'object',
                    'additionalProperties' => [
                        'location' => 'json'
                    ]
                ]
            ]
        ]);
    }
}
```

To consume your API description with an API client, simply extend the `AbstractApiClient` base class and override the 
`$apiDescription` field with your API description's class name:

```php
<?php

use Edmund\PhpApiClient\AbstractApiClient;

class TwitterUrlsApiClient extends AbstractApiClient
{
    protected $apiDescription = TwitterUrlsApiDescription::class;
}
```

Finally, instantiate your API client and make API calls using the method names you defined in the API description,
under the `operations` section:

```php
<?php

$twitterUrls = new TwitterUrlsApiClient();

$response = $twitterUrls->count(['url' => 'http://www.google.com']);

var_dump($response);
```

Response:

```
array(2) {
  'count' =>
  int(25256927)
  'url' =>
  string(22) "http://www.google.com/"
}
```

## Authenticators

Should you need authentication for your API requests, you can authenticate your client with an `Authenticator` object.
`Authenticator` objects intercept outgoing HTTP requests and sign them before they are sent out. 

This is very useful for various authentication flows, such as OAuth2 or HMAC, where requests must have additional 
parameters appended to them (e.g., access tokens). 
 
Simply extend the `AbstractAuthenticator` base class and define the authentication logic required 
within the `sign()` method:

```php
<?php

use GuzzleHttp\Event\BeforeEvent;
use Edmund\PhpApiClient\Auth\AbstractAuthenticator;

class TwitterUrlsOauthAuthenticator extends AbstractAuthenticator
{
    function sign(BeforeEvent $event)
    {
        $event->getRequest()->getQuery()->add('access_token', 'token_value');
    }
}
```

In this example, `TwitterUrlsOauthAuthenticator` appends `access_token=token_value` to all GET requests.

In order to authenticate the client using the `Authenticator`, simply override the `$authenticators` array
field from `AbstractApiClient` with an associative array of `Authenticator` class names:

```php
<?php

use Edmund\PhpApiClient\AbstractApiClient;

class TwitterUrlsApiClient extends AbstractApiClient
{
    protected $apiDescription = TwitterUrlsApiDescription::class;
    protected $authenticators = [
        'oauth2' => TwitterUrlsOauthAuthenticator::class
    ];
}
```

The key value that the `Authenticator` class name is mapped to (in this case, `'oauth2'`) is used to locate the desired
`Authenticator` object for the client during initialization. Simply pass `authType` in your configuration array with the 
desired key to apply the `Authenticator` object to your client:

```php
<?php

$oauthClient = new TwitterUrlsApiClient(['authType' => 'oauth2']);

$response = $oauthClient->count(['url' => 'http://www.google.com']);

// GET http://urls.api.twitter.com/1/urls/count.json?url=http%3A%2F%2Fwww.google.com&access_token=token_value
```

## Debugging

The `AbstractApiClient` base class has a built-in `LogSubscriber` that can be toggled on/off 
by passing in a `debug` flag in the configuration array for the client (note that the value must be `true`):

Using the OAuth2 client from the previous example:

```php
<?php

$oauthClient = new TwitterUrlsApiClient([
    'authType'  => 'oauth2',
    'debug'     => true
]);

$response = $oauthClient->count(['url' => 'http://www.google.com']);
```

Output:
```
[info] >>>>>>>>
GET /1/urls/count.json?url=http%3A%2F%2Fwww.google.com&access_token=token_value HTTP/1.1
Host: urls.api.twitter.com
User-Agent: Guzzle/5.3.0 curl/7.38.0 PHP/5.6.13-0+deb8u1


<<<<<<<<
HTTP/1.1 200 OK
cache-control: must-revalidate, max-age=900
content-type: application/json;charset=utf-8
expires: Fri, 16 Oct 2015 01:49:46 GMT
last-modified: Fri, 16 Oct 2015 01:34:46 GMT
server: tsa_b
x-connection-hash: f3a8824171a693f97ff1f69e0b675980
x-response-time: 4
Content-Length: 52
Accept-Ranges: bytes
Date: Fri, 16 Oct 2015 01:34:46 GMT
Via: 1.1 varnish
Age: 0
Connection: keep-alive
X-Served-By: cache-tw-iad2-cr1-8-TWIAD2
X-Cache: MISS
X-Cache-Hits: 0
Vary: Accept-Encoding

{"count":25306973,"url":"http:\/\/www.google.com\/"}
```

Additionally, a PSR-3 compatible Logger can be passed into the configuration array as well for usage
by passing in a `debugLogger` option:

```php
<?php

$logger = new \Monolog\Logger('name'); 
$twitterUrls = new TwitterUrlsApiClient([
    'debug'         => true,
    'debugLogger'   => $logger
]);
```

## Documentation

[See the official Guzzle Web Service client documentation for more information.](http://guzzle3.readthedocs.org/webservice-client/webservice-client.html)

## Contributing

Please submit any pull requests to the `develop` branch.
Pull requests are welcome and will be happily accepted given that the contributed code follows the [PSR-2 Coding Style Guide](http://www.php-fig.org/psr/psr-2/).

## License

For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
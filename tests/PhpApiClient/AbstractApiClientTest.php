<?php

/**
 * This file is part of the php-api-client package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author    Edmund Luong <edmundvmluong@gmail.com>
 * @copyright Copyright (c) 2015, Edmund Luong
 * @link      https://github.com/edmundluong/php-api-client
 */

namespace Edmund\PhpApiClient;

use Edmund\PhpApiClient\Auth\AbstractAuthenticator;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Message\MessageFactory;
use GuzzleHttp\Ring\Client\CurlHandler;
use GuzzleHttp\Subscriber\Log\LogSubscriber;

/**
 * Tests the Twitter URLs API client, which tests the abstract API client.
 */
class AbstractApiClientTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_makes_an_api_call_with_magic_methods()
    {
        $twitterUrls = new TwitterUrlsApiClient();

        $response = $twitterUrls->count(['url' => 'http://www.google.com']);

        $this->assertTrue(array_key_exists('count', $response));
        $this->assertTrue(array_key_exists('url', $response));
        $this->assertEquals('http://www.google.com/', $response['url']);
    }

    /** @test */
    public function it_throws_a_malformed_api_description_exception_when_given_an_invalid_format()
    {
        $this->setExpectedException('\Edmund\PhpApiClient\Exceptions\InvalidDescriptionException');
        new TwitterUrlsApiClient(['apiDescription' => 'foo']);
    }

    /** @test */
    public function it_throws_an_unregistered_authenticator_exception_when_given_an_unknown_authentication_type()
    {
        $this->setExpectedException('\Edmund\PhpApiClient\Exceptions\UnregisteredAuthenticatorException');
        new TwitterUrlsApiClient(['authType' => 'foo']);
    }

    /** @test */
    public function it_can_be_switched_into_debug_mode_with_a_flag()
    {
        $debugOff = new TwitterUrlsApiClient(['debug' => false]);
        $listeners = $debugOff->getHttpClient()->getEmitter()->listeners();
        $this->assertCount(0, $listeners);

        $debugOn = new TwitterUrlsApiClient(['debug' => true]);
        $listeners = $debugOn->getHttpClient()->getEmitter()->listeners();
        $this->assertCount(2, $listeners);
        $this->assertInstanceOf(LogSubscriber::class, $listeners['error'][0][0]);
    }

    /** @test */
    public function it_can_sign_requests_with_an_authenticator()
    {
        $twitterUrls = new TwitterUrlsApiClient();
        $this->assertCount(0, $twitterUrls->getHttpClient()->getEmitter()->listeners());

        $twitterUrls = new TwitterUrlsApiClient(['authType' => 'oauth2']);
        $this->assertEquals('oauth2', $twitterUrls->getAuthType());
        $this->assertEquals('oauth2', $twitterUrls->getHttpClient()->getDefaultOption('auth'));

        $listeners = $twitterUrls->getHttpClient()->getEmitter()->listeners();
        $this->assertCount(1, $listeners);
        $this->assertInstanceOf(TwitterUrlsOauthAuthenticator::class, $listeners['before'][0][0]);
        $this->assertEquals('sign', $listeners['before'][0][1]);
    }

    /** @test */
    public function it_extracts_guzzle_config_options_from_the_input_array()
    {
        $twitterUrls = new TwitterUrlsApiClient([
            'base_url'        => 'foo',
            'defaults'        => [],
            'emitter'         => 'baz',
            'handler'         => new CurlHandler(),
            'message_factory' => new MessageFactory()
        ]);

        $guzzleConfig = $twitterUrls->getGuzzleConfig();
        $this->assertCount(0, $twitterUrls->getApiConfig());
        $this->assertCount(5, $guzzleConfig);
        $this->assertEquals('foo', $guzzleConfig['base_url']);
        $this->assertEquals([], $guzzleConfig['defaults']);
        $this->assertEquals('baz', $guzzleConfig['emitter']);
        $this->assertInstanceOf(CurlHandler::class, $guzzleConfig['handler']);
        $this->assertInstanceOf(MessageFactory::class, $guzzleConfig['message_factory']);
    }
}

/**
 * Example Twitter URLs API description.
 * The API endpoint is: http://urls.api.twitter.com/1/urls/count.json
 */
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

/**
 * Example Twitter URLS API OAuth2 Authenticator.
 */
class TwitterUrlsOauthAuthenticator extends AbstractAuthenticator
{
    function sign(BeforeEvent $event)
    {
        if ($this->requestHasAuth($event->getRequest(), 'oauth2')) {
            $event->getRequest()->getQuery()->add('access_token', 'token_value');
        }
    }
}

/**
 * Example Twitter URLs API client.
 */
class TwitterUrlsApiClient extends AbstractApiClient
{
    protected $apiDescription = TwitterUrlsApiDescription::class;
    protected $authenticators = ['oauth2' => TwitterUrlsOauthAuthenticator::class];
}

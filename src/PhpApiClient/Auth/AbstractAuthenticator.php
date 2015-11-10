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

namespace Edmund\PhpApiClient\Auth;

use Edmund\PhpApiClient\AbstractApiClient;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\RequestEvents;
use GuzzleHttp\Event\SubscriberInterface;

/**
 * A base authenticator object that should be used to sign API requests with.
 */
abstract class AbstractAuthenticator implements SubscriberInterface
{
    /**
     * @var AbstractApiClient $client
     */
    protected $client;

    /**
     * Authenticator constructor takes in a configuration array.
     *
     * @param AbstractApiClient $client
     */
    public function __construct(AbstractApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array
     */
    public function getEvents()
    {
        return ['before' => ['sign', RequestEvents::SIGN_REQUEST]];
    }

    /**
     * Signs an API request using an authentication flow.
     *
     * @param BeforeEvent $event
     */
    abstract public function sign(BeforeEvent $event);

    /**
     * @return  AbstractApiClient
     */
    public function getClient()
    {
        return $this->client;
    }
}
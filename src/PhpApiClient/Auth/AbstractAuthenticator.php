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

use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\RequestEvents;
use GuzzleHttp\Event\SubscriberInterface;
use GuzzleHttp\Message\RequestInterface;

/**
 * A base authenticator object that should be used to sign API requests with.
 */
abstract class AbstractAuthenticator implements SubscriberInterface
{
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
    abstract function sign(BeforeEvent $event);

    /**
     * Returns the request's authentication type.
     *
     * @param RequestInterface $request
     * @param string           $authType
     *
     * @return string
     */
    protected function requestHasAuth(RequestInterface $request, $authType)
    {
        return $request->getConfig()['auth'] === $authType;
    }
}
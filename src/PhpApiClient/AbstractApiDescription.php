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

/**
 * An extension of the Guzzle Description object.
 * Enforces the load() method, which is used to lazy-load the API description for the
 *
 *
 */
abstract class AbstractApiDescription extends \GuzzleHttp\Command\Guzzle\Description
{
    /**
     * Loads the API service description.
     *
     * @return static
     */
    public abstract function load();
}
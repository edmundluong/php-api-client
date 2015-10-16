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

namespace Edmund\PhpApiClient\Utils;

/**
 * Configuration utilities and helpers.
 */
final class ConfigUtils
{
    /**
     * @var  array $GUZZLE_CONFIG_KEYS
     */
    public static $GUZZLE_CONFIG_KEYS = [
        'base_url',
        'defaults',
        'emitter',
        'handler',
        'message_factory'
    ];

    /**
     * Alias for the opposite of configurationExists().
     *
     * @param array $config
     * @param       $key
     *
     * @return  bool
     */
    public static function configurationEmpty(array $config, $key)
    {
        return !self::configurationExists($config, $key);
    }

    /**
     * Determines whether a setting exists in a configuration array.
     *
     * @param array  $config
     * @param string $key
     *
     * @return bool
     */
    public static function configurationExists(array $config, $key)
    {
        return array_key_exists($key, $config) && !empty($config[$key]);
    }
}
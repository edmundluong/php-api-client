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

namespace Edmund\PhpApiClient\Exceptions;

/**
 * Indicates that the API description used by the client is cannot be utilized,
 * and therefore the client cannot be initialized.
 *
 * Error could be due to an unknown/missing/invalid/malformed API description.
 */
class InvalidDescriptionException extends \Exception
{
}
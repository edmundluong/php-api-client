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
use Edmund\PhpApiClient\Exceptions\InvalidDescriptionException;
use Edmund\PhpApiClient\Exceptions\UnregisteredAuthenticatorException;
use Edmund\PhpApiClient\Utils\ConfigUtils;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Subscriber\Log\Formatter;
use GuzzleHttp\Subscriber\Log\LogSubscriber;
use Psr\Log\LoggerInterface;

/**
 * Provides a layer of abstraction over the Guzzle Services client to facilitate
 * common tasks such as logging and request signing and API authentication.
 */
abstract class AbstractApiClient extends \GuzzleHttp\Command\Guzzle\GuzzleClient
{
    /**
     * @var AbstractAuthenticator[] $authenticators
     */
    protected $authenticators = [];

    /**
     * @var Description $apiDescription
     */
    protected $apiDescription = null;

    /**
     * @var string $authType
     */
    protected $authType = null;

    /**
     * @var Client $client
     */
    protected $client = null;

    /**
     * @var  array $apiConfig
     */
    protected $apiConfig = [];

    /**
     * @var  array $guzzleConfig
     */
    protected $guzzleConfig = [];

    /**
     * @var  bool $debugMode
     */
    protected $debugMode = false;

    /**
     * @var LoggerInterface $debugLogger
     */
    protected $debugLogger = null;

    /**
     * Instantiates an abstract API client.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->parseConfig(array_merge($this->apiConfig, $config));

        parent::__construct($this->initializeClient(), $this->resolveApiDescription());
    }

    /**
     * Parses the input array for the API client and sets various properties.
     * Returns the raw config
     *
     * @param array $config
     *
     * @return array
     */
    private function parseConfig(array $config = [])
    {
        $this->apiConfig = $this->extractGuzzleConfig($config);

        if (ConfigUtils::configurationExists($this->apiConfig, 'apiDescription')) {
            $this->apiDescription = $this->apiConfig['apiDescription'];
        }
        if (ConfigUtils::configurationExists($this->apiConfig, 'authType')) {
            $this->authType = $this->apiConfig['authType'];
        }
        if (ConfigUtils::configurationExists($this->apiConfig, 'debug')) {
            $this->debugMode = $this->apiConfig['debug'];
            if (ConfigUtils::configurationExists($this->apiConfig, 'debugLogger')) {
                $this->debugLogger = $this->apiConfig['debugLogger'];
            }
        }
    }

    /**
     * Extracts the Guzzle-specific configuration options from the input configuration array.
     * Returns a stripped-down configuration array, with the Guzzle options removed.
     *
     * @param $config
     *
     * @return array
     */
    private function extractGuzzleConfig($config)
    {
        foreach (ConfigUtils::$GUZZLE_CONFIG_KEYS as $guzzleOption) {
            if (array_key_exists($guzzleOption, $config)) {
                $this->guzzleConfig[$guzzleOption] = $config[$guzzleOption];
                unset($config[$guzzleOption]);
            }
        }

        return $config;
    }

    /**
     * Initializes the API client.
     *
     * @return ClientInterface
     */
    private function initializeClient()
    {
        $this->client = new Client($this->guzzleConfig);

        if ($this->isInDebugMode()) {
            $this->setDebugger();
        }
        if ($this->needsAuthentication()) {
            $this->authenticate();
        }

        return $this->client;
    }

    /**
     * Returns whether the API client is in debug mode or not.
     *
     * @return  bool
     */
    public function isInDebugMode()
    {
        return $this->debugMode === true;
    }

    /**
     * Attaches a debugger to the API client.
     */
    private function setDebugger()
    {
        $this->client
            ->getEmitter()
            ->attach(new LogSubscriber($this->debugLogger, Formatter::DEBUG));
    }

    /**
     * Determines whether the client needs to be authenticated with the given type.
     *
     * @return bool
     */
    protected function needsAuthentication()
    {
        return !empty($this->authType);
    }

    /**
     * Authenticates the API client by attaching an authenticator that signs requests.
     *
     * @throws UnregisteredAuthenticatorException
     */
    private function authenticate()
    {
        self::assertValidAuthType();

        $this->client
            ->getEmitter()
            ->attach(new $this->authenticators[$this->authType]($this));
    }

    /**
     * Asserts that the API client's authentication type is registered.
     *
     * @throws UnregisteredAuthenticatorException
     */
    private function assertValidAuthType()
    {
        if (
            !array_key_exists($this->authType, $this->authenticators)
            || !class_exists($this->authenticators[$this->authType])
            || !is_subclass_of($this->authenticators[$this->authType], AbstractAuthenticator::class)
        ) {
            throw new UnregisteredAuthenticatorException(
                $this->authType . ' is not a registered authentication type'
            );
        }
    }

    /**
     * Resolves the API service description for usage.
     *
     * @throws InvalidDescriptionException
     */
    private function resolveApiDescription()
    {
        $resolveByClassNameAttempted = false;
        while (!$this->apiDescription instanceof AbstractApiDescription) {
            if ($resolveByClassNameAttempted) {
                $error = (is_null($this->apiDescription))
                    ? 'No API service description found'
                    : 'Malformed API service description';

                throw new InvalidDescriptionException($error);
            }

            $resolveByClassNameAttempted = true;
            if (class_exists($this->apiDescription)
                && is_subclass_of($this->apiDescription, AbstractApiDescription::class)
            ) {
                $this->apiDescription = new $this->apiDescription([]);
            }
        }

        return $this->apiDescription = $this->apiDescription->load();
    }

    /**
     * @return  Auth\AbstractAuthenticator[]
     */
    public function getAuthenticators()
    {
        return $this->authenticators;
    }

    /**
     * @return  string
     */
    public function getAuthType()
    {
        return $this->authType;
    }

    /**
     * @return  array
     */
    public function getApiConfig()
    {
        return $this->apiConfig;
    }

    /**
     * @return  array
     */
    public function getGuzzleConfig()
    {
        return $this->guzzleConfig;
    }

    /**
     * @return  LoggerInterface
     */
    public function getDebugLogger()
    {
        return $this->debugLogger;
    }
}
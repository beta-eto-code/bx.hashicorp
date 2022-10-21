<?php

namespace Bx\HashiCorp;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use BitrixPSR16\Cache;
use BitrixPSR17\HttpFactory;
use Bx\OptionHolder\CachedOptionHolder;
use Bx\OptionHolder\OptionHolderInterface;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Vault\AuthenticationStrategies\AbstractPathAuthenticationStrategy;
use Vault\AuthenticationStrategies\AppRoleAuthenticationStrategy;
use Vault\AuthenticationStrategies\AuthenticationStrategy;
use Vault\AuthenticationStrategies\TokenAuthenticationStrategy;
use Vault\BaseClient;
use Vault\Client;
use Vault\Exceptions\RuntimeException;

class Factory
{
    private const MODULE_ID = 'bx.hashicorp';

    /**
     * @param string $defaultKeyspace
     * @param ClientInterface|null $httpClient
     * @param LoggerInterface|null $logger
     * @param CacheInterface|null $cache
     * @param int $ttl
     * @return OptionHolderInterface
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     * @throws ClientExceptionInterface
     * @throws InvalidSettingsException
     * @throws RuntimeException
     */
    public static function crateCached(
        string $defaultKeyspace,
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null,
        ?CacheInterface $cache = null,
        int $ttl = 3600
    ): OptionHolderInterface {
        $cache = $cache ?? new Cache($ttl);
        $optionHolder = static::create($defaultKeyspace, $httpClient, $logger);
        return new CachedOptionHolder($optionHolder, $cache, $ttl);
    }

    /**
     * @param string $defaultKeyspace
     * @param ClientInterface|null $httpClient
     * @param LoggerInterface|null $logger
     * @return OptionHolderInterface
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     * @throws ClientExceptionInterface
     * @throws InvalidSettingsException
     * @throws RuntimeException
     */
    public static function create(
        string $defaultKeyspace,
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null
    ): OptionHolderInterface {
        $client = static::createClient($httpClient, $logger);
        return new HashiCorpOptionHolder($client, $defaultKeyspace);
    }

    /**
     * @param ClientInterface|null $httpClient
     * @param LoggerInterface|null $logger
     * @return BaseClient
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     * @throws ClientExceptionInterface
     * @throws InvalidSettingsException
     * @throws RuntimeException
     * @throws Exception
     * @psalm-suppress MoreSpecificReturnType,UndefinedDocblockClass
     */
    private static function createClient(
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null
    ): BaseClient {

        $uriString = static::getOptionValue('URI');
        if (empty($uriString)) {
            throw new InvalidSettingsException('[hashiCorp] uri is empty');
        }

        $httpFactory = new HttpFactory();
        $httpClient = $httpClient ?? new \BitrixPSR18\Client();
        $client = new Client(
            new Uri($uriString),
            $httpClient,
            $httpFactory,
            $httpFactory,
            $logger
        );

        $nameSpace = static::getOptionValue('NAMESPACE');
        if (!empty($nameSpace)) {
            $client->setNamespace($nameSpace);
        }

        $authStrategy = static::createAuthStrategy();
        /**
         * @psalm-suppress UndefinedMethod
         */
        $client->setAuthenticationStrategy($authStrategy)->authenticate();
        return $client;
    }

    /**
     * @return AuthenticationStrategy
     * @throws Exception
     */
    private static function createAuthStrategy(): AuthenticationStrategy
    {
        $authType = static::getOptionValue('AUTH_TYPE');
        if (empty($authType)) {
            throw new InvalidSettingsException('[hashiCorp] auth type is not select');
        }

        $invalidAuthTypeException = new InvalidSettingsException('[hashiCorp] invalid auth type');
        if (!class_exists($authType)) {
            throw $invalidAuthTypeException;
        }

        if (is_a($authType, AbstractPathAuthenticationStrategy::class, true)) {
            /**
             * @psalm-suppress InvalidArgument
             */
            return static::createAuthStrategyWithUsernamePassword($authType);
        }

        if (is_a($authType, TokenAuthenticationStrategy::class, true)) {
            return static::createAuthStrategyWithToken();
        }

        if (is_a($authType, AppRoleAuthenticationStrategy::class, true)) {
            return static::createAuthStrategyWithAppCredential();
        }

        throw $invalidAuthTypeException;
    }

    /**
     * @param class-string<AbstractPathAuthenticationStrategy> $className
     * @return AuthenticationStrategy
     * @throws Exception
     * @psalm-suppress MoreSpecificReturnType
     */
    private static function createAuthStrategyWithUsernamePassword(string $className): AuthenticationStrategy
    {
        /**
         * @psalm-suppress RedundantConditionGivenDocblockType
         */
        if (!is_a($className, AbstractPathAuthenticationStrategy::class, true)) {
            throw new InvalidSettingsException('[hashiCorp] invalid auth strategy');
        }

        $username = static::getOptionValue('USERNAME');
        if (empty($username)) {
            throw new InvalidSettingsException('[hashiCorp] username is empty');
        }

        $password = static::getOptionValue('PASSWORD');
        if (empty($password)) {
            throw new InvalidSettingsException('[hashiCorp] password is empty');
        }

        /**
         * @psalm-suppress UndefinedClass,LessSpecificReturnStatement,UnsafeInstantiation
         */
        return new $className($username, $password);
    }

    /**
     * @return AuthenticationStrategy
     * @throws Exception
     */
    private static function createAuthStrategyWithToken(): AuthenticationStrategy
    {
        $token = static::getOptionValue('TOKEN');
        if (empty($token)) {
            throw new InvalidSettingsException('[hashiCorp] token is empty');
        }

        return new TokenAuthenticationStrategy($token);
    }

    /**
     * @return AuthenticationStrategy
     * @throws Exception
     */
    private static function createAuthStrategyWithAppCredential(): AuthenticationStrategy
    {
        $roleId = static::getOptionValue('ROLE_ID');
        if (empty($roleId)) {
            throw new InvalidSettingsException('[hashiCorp] roleId is empty');
        }

        $secretId = static::getOptionValue('SECRET_ID');
        if (empty($secretId)) {
            throw new InvalidSettingsException('[hashiCorp] secretId is empty');
        }

        return new AppRoleAuthenticationStrategy($roleId, $secretId);
    }

    /**
     * @param string $key
     * @return mixed
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     */
    private static function getOptionValue(string $key)
    {
        return Option::get(static::MODULE_ID, $key) ?: null;
    }
}

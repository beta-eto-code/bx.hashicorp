<?php

namespace Bx\HashiCorp;

use Bitrix\Main\Config\Option;
use BitrixPSR17\HttpFactory;
use Bx\OptionHolder\OptionHolderInterface;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Vault\AuthenticationStrategies\AbstractPathAuthenticationStrategy;
use Vault\AuthenticationStrategies\AppRoleAuthenticationStrategy;
use Vault\AuthenticationStrategies\AuthenticationStrategy;
use Vault\AuthenticationStrategies\TokenAuthenticationStrategy;
use Vault\BaseClient;
use Vault\CachedClient;
use Vault\Client;

class Factory
{
    private const MODULE_ID = 'bx.hashicorp';

    /**
     * @param string $defaultKeyspace
     * @param ClientInterface|null $httpClient
     * @param LoggerInterface|null $logger
     * @return OptionHolderInterface
     * @throws Exception
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
     * @param string $defaultKeyspace
     * @param ClientInterface|null $httpClient
     * @param LoggerInterface|null $logger
     * @return OptionHolderInterface
     * @throws InvalidSettingsException
     */
    public static function crateCached(
        string $defaultKeyspace,
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null
    ): OptionHolderInterface {
        $client = static::createCachedClient($httpClient, $logger);
        return new HashiCorpOptionHolder($client, $defaultKeyspace);
    }

    /**
     * @param ClientInterface|null $httpClient
     * @param LoggerInterface|null $logger
     * @return BaseClient
     * @throws Exception
     */
    private static function createClient(
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null
    ): BaseClient {
        return static::createClientFromClass(Client::class, $httpClient, $logger);
    }

    /**
     * @param ClientInterface|null $httpClient
     * @param LoggerInterface|null $logger
     * @return BaseClient
     * @throws InvalidSettingsException
     * @psalm-suppress UndefinedDocblockClass
     */
    private static function createCachedClient(
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null
    ): BaseClient {
        return static::createClientFromClass(CachedClient::class, $httpClient, $logger);
    }

    /**
     * @param class-string<BaseClient> $class
     * @param ClientInterface|null $httpClient
     * @param LoggerInterface|null $logger
     * @return BaseClient
     * @throws InvalidSettingsException
     * @throws Exception
     * @psalm-suppress MoreSpecificReturnType,UndefinedDocblockClass
     */
    private static function createClientFromClass(
        string $class,
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null
    ): BaseClient {
        /**
         * @psalm-suppress RedundantConditionGivenDocblockType
         */
        if (!($class instanceof BaseClient)) {
            throw new Exception('[hashiCorp] invalid client class');
        }

        $uriString = static::getOptionValue('URI');
        if (empty($uriString)) {
            throw new InvalidSettingsException('[hashiCorp] uri is empty');
        }

        $httpFactory = new HttpFactory();
        $httpClient = $httpClient ?? new \BitrixPSR18\Client();
        /**
         * @psalm-suppress UndefinedClass
         */
        $client = new $class(
            new Uri($uriString),
            $httpClient,
            $httpFactory,
            $httpFactory,
            $logger
        );

        if (!($client instanceof BaseClient)) {
            throw new Exception('[hashiCorp] invalid client class');
        }

        $nameSpace = static::getOptionValue('NAMESPACE');
        if (!empty($nameSpace)) {
            /**
             * @psalm-suppress UndefinedMethod
             */
            $client->setNamespace($nameSpace);
        }

        $authStrategy = static::createAuthStrategy();
        /**
         * @psalm-suppress UndefinedMethod
         */
        $client->setAuthenticationStrategy($authStrategy);
        return $client;
    }

    /**
     * @return AuthenticationStrategy
     * @throws Exception
     */
    private static function createAuthStrategy(): AuthenticationStrategy
    {
        $authType = static::getOptionValue('AUT_TYPE');
        if (empty($authType)) {
            throw new InvalidSettingsException('[hashiCorp] auth type is not select');
        }

        $invalidAuthTypeException = new InvalidSettingsException('[hashiCorp] invalid auth type');
        if (class_exists($authType)) {
            throw $invalidAuthTypeException;
        }

        if ($authType instanceof AbstractPathAuthenticationStrategy) {
            /**
             * @psalm-suppress InvalidArgument
             */
            return static::createAuthStrategyWithUsernamePassword($authType);
        }

        if ($authType instanceof TokenAuthenticationStrategy) {
            return static::createAuthStrategyWithToken();
        }

        if ($authType instanceof AppRoleAuthenticationStrategy) {
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
        if (!($className instanceof AbstractPathAuthenticationStrategy)) {
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
         * @psalm-suppress UndefinedClass,LessSpecificReturnStatement
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
     */
    private static function getOptionValue(string $key)
    {
        return Option::get(static::MODULE_ID, $key) ?: null;
    }
}

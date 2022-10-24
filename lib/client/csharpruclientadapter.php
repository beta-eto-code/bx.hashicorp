<?php

namespace Bx\HashiCorp\Client;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use BitrixPSR17\HttpFactory;
use Bx\HashiCorp\InvalidSettingsException;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Psr\Cache\InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Vault\AuthenticationStrategies\AbstractPathAuthenticationStrategy;
use Vault\AuthenticationStrategies\AppRoleAuthenticationStrategy;
use Vault\AuthenticationStrategies\AuthenticationStrategy;
use Vault\AuthenticationStrategies\TokenAuthenticationStrategy;
use Vault\BaseClient;
use Vault\Client;
use Vault\Exceptions\RuntimeException;

class CSharpRuClientAdapter implements HashiCorpVaultClientInterface
{
    private BaseClient $client;
    private bool $isAuthenticated = false;

    public function __construct(BaseClient $client)
    {
        $this->client = $client;
    }

    /**
     * @throws ArgumentNullException
     * @throws InvalidSettingsException
     * @throws ArgumentOutOfRangeException
     * @throws ClientExceptionInterface
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws Exception
     */
    public static function initFromModuleOptions(
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null
    ): HashiCorpVaultClientInterface {
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
        $client->setAuthenticationStrategy($authStrategy);
        return new CSharpRuClientAdapter($client);
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
        return Option::get('bx.hashicorp', $key) ?: null;
    }

    /**
     * @param string $path
     * @return array|null
     * @throws ClientExceptionInterface
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function getDataByPath(string $path): ?array
    {
        $this->authenticate();
        /**
         * @psalm-suppress UndefinedMethod
         */
        $response = $this->client->read($path);
        return $response->getData();
    }

    /**
     * @param string $path
     * @param array $data
     * @return void
     * @throws ClientExceptionInterface
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function setDataByPath(string $path, array $data): void
    {
        $this->authenticate();
        /**
         * @psalm-suppress UndefinedMethod
         */
        $this->client->write($path, $data);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    private function authenticate()
    {
        if ($this->isAuthenticated === false && $this->client instanceof Client) {
            $this->client->authenticate();
        }
    }
}

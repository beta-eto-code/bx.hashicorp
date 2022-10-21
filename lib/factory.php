<?php

namespace Bx\HashiCorp;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use BitrixPSR16\Cache;
use Bx\HashiCorp\Client\CSharpRuClientAdapter;
use Bx\OptionHolder\CachedOptionHolder;
use Bx\OptionHolder\OptionHolderInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Vault\Exceptions\RuntimeException;

class Factory
{
    /**
     * @param string $defaultKeyspace
     * @param int $kvEngineVersion
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
    public static function createCached(
        string $defaultKeyspace,
        int $kvEngineVersion = 2,
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null,
        ?CacheInterface $cache = null,
        int $ttl = 3600
    ): OptionHolderInterface {
        $cache = $cache ?? new Cache($ttl);
        $optionHolder = static::create($defaultKeyspace, $kvEngineVersion, $httpClient, $logger);
        return new CachedOptionHolder($optionHolder, $cache, $ttl);
    }

    /**
     * @param string $defaultKeyspace
     * @param int $kvEngineVersion
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
        int $kvEngineVersion = 2,
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null
    ): OptionHolderInterface {
        $client = CSharpRuClientAdapter::initFromModuleOptions($httpClient, $logger);
        /**
         * @psalm-suppress TooManyArguments
         */
        return new HashiCorpOptionHolder($client, $defaultKeyspace, $kvEngineVersion);
    }
}

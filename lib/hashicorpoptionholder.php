<?php

namespace Bx\HashiCorp;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bx\OptionHolder\OptionHolderInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Throwable;
use Vault\BaseClient;

class HashiCorpOptionHolder implements OptionHolderInterface
{
    private BaseClient $client;
    private string $defaultKeySpace;

    public function __construct(BaseClient $client, string $defaultKeySpace)
    {
        $this->client = $client;
        $this->defaultKeySpace = $defaultKeySpace;
    }

    public function getDefaultKeySpace(): string
    {
        return $this->defaultKeySpace;
    }

    /**
     * @param string $key
     * @param string|null $keySpace
     * @param mixed $defaultValue
     * @return mixed|null
     * @throws ClientExceptionInterface
     */
    public function getOptionValue(string $key, ?string $keySpace = null, $defaultValue = null)
    {
        $path = $keySpace ?: $this->defaultKeySpace;
        $data = $this->getDataByPath($path) ?? [];
        return $data[$key] ?? $defaultValue;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param string|null $keySpace
     * @return Result
     */
    public function setOptionValue(string $key, $value, ?string $keySpace = null): Result
    {
        $result = new Result();
        try {
            $path = $keySpace ?: $this->defaultKeySpace;
            $data = $this->getDataByPath($path) ?? [];
            $data[$key] = $value;
            /**
             * @psalm-suppress UndefinedMethod
             */
            $this->client->write($path, $data);
        } catch (Throwable $e) {
            return $result->addError(new Error($e->getMessage()));
        }

        return $result;
    }

    /**
     * @param string $path
     * @return array|null
     * @throws ClientExceptionInterface
     */
    private function getDataByPath(string $path): ?array
    {
        $path = "/secret/data/$path";
        try {
            /**
             * @psalm-suppress UndefinedMethod
             */
            $response = $this->client->read($path);
            $data = $response->getData();
            return $data['data'] ?? null;
        } catch (Throwable $e) {
        }

        return null;
    }
}

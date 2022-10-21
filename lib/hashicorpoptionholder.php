<?php

namespace Bx\HashiCorp;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bx\HashiCorp\Client\HashiCorpVaultClientInterface;
use Bx\OptionHolder\OptionHolderInterface;
use Throwable;

class HashiCorpOptionHolder implements OptionHolderInterface
{
    private HashiCorpVaultClientInterface $client;
    private string $defaultKeySpace;
    /**
     * @var RWHandlerInterface
     */
    private $rwHandler;

    public function __construct(
        HashiCorpVaultClientInterface $client,
        string $defaultKeySpace,
        int $kvEngineVersion = 2
    ) {
        $this->client = $client;
        $this->defaultKeySpace = $defaultKeySpace;
        /**
         * @psalm-suppress InvalidPropertyAssignmentValue
         */
        $this->rwHandler = $kvEngineVersion > 1 ? RWHandlerV2::class : RWHandlerV1::class;
    }

    public function getDefaultKeySpace(): string
    {
        return $this->defaultKeySpace;
    }

    public function setRWHandler(RWHandlerInterface $rwHandler): void
    {
        $this->rwHandler = $rwHandler;
    }

    /**
     * @param string $key
     * @param string|null $keySpace
     * @param mixed $defaultValue
     * @return mixed|null
     */
    public function getOptionValue(string $key, ?string $keySpace = null, $defaultValue = null)
    {
        $keySpace = $keySpace ?: $this->defaultKeySpace;
        $data = $this->rwHandler::getDataFromKeySpace($this->client, $keySpace);
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
            $keySpace = $keySpace ?: $this->defaultKeySpace;
            $this->rwHandler::setDataToKeySpace($this->client, $keySpace, [$key => $value]);
        } catch (Throwable $e) {
            return $result->addError(new Error($e->getMessage()));
        }

        return $result;
    }
}

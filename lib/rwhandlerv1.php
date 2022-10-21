<?php

namespace Bx\HashiCorp;

use Bx\HashiCorp\Client\HashiCorpVaultClientInterface;

class RWHandlerV1 implements RWHandlerInterface
{
    public static function getDataFromKeySpace(HashiCorpVaultClientInterface $client, string $keySpace): ?array
    {
        $path = "/secret/$keySpace";
        return $client->getDataByPath($path);
    }

    public static function setDataToKeySpace(HashiCorpVaultClientInterface $client, string $keySpace, array $data): void
    {
        $dataFromKeySpace = static::getDataFromKeySpace($client, $keySpace) ?? [];
        $data = array_merge($dataFromKeySpace, $data);
        $path = "/secret/$keySpace";
        $client->setDataByPath($path, $data);
    }
}

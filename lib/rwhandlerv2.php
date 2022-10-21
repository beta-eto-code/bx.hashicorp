<?php

namespace Bx\HashiCorp;

use Bx\HashiCorp\Client\HashiCorpVaultClientInterface;

class RWHandlerV2 implements RWHandlerInterface
{
    public static function getDataFromKeySpace(HashiCorpVaultClientInterface $client, string $keySpace): ?array
    {
        $path = "/secret/data/$keySpace";
        $data = $client->getDataByPath($path);
        return $data['data'] ?? null;
    }

    public static function setDataToKeySpace(HashiCorpVaultClientInterface $client, string $keySpace, array $data): void
    {
        $dataFromKeySpace = static::getDataFromKeySpace($client, $keySpace) ?? [];
        $data = array_merge($dataFromKeySpace, $data);
        $path = "/secret/data/$keySpace";
        $client->setDataByPath($path, ['data' => $data]);
    }
}

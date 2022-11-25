<?php

namespace Bx\HashiCorp;

use Bx\HashiCorp\Client\HashiCorpVaultClientInterface;

class RWHandlerV2 implements RWHandlerInterface
{
    public static function getDataFromKeySpace(
        HashiCorpVaultClientInterface $client,
        string $keySpace,
        string $kvPath = 'secret',
        array $keySpaceMap = []
    ): ?array {
        $keySpace = $keySpaceMap[$keySpace] ?? $keySpace;
        $path = "/$kvPath/data/$keySpace";
        $data = $client->getDataByPath($path);
        return $data['data'] ?? null;
    }

    public static function setDataToKeySpace(
        HashiCorpVaultClientInterface $client,
        string $keySpace,
        array $data,
        string $kvPath = 'secret',
        array $keySpaceMap = []
    ): void {
        $keySpace = $keySpaceMap[$keySpace] ?? $keySpace;
        $dataFromKeySpace = static::getDataFromKeySpace($client, $keySpace, $kvPath) ?? [];
        $data = array_merge($dataFromKeySpace, $data);
        $path = "/$kvPath/data/$keySpace";
        $client->setDataByPath($path, ['data' => $data]);
    }
}

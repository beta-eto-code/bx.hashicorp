<?php

namespace Bx\HashiCorp;

use Bx\HashiCorp\Client\HashiCorpVaultClientInterface;

class RWHandlerV1 implements RWHandlerInterface
{
    public static function getDataFromKeySpace(
        HashiCorpVaultClientInterface $client,
        string $keySpace,
        string $kvPath = 'secret',
        array $keySpaceMap = []
    ): ?array {
        $keySpace = $keySpaceMap[$keySpace] ?? $keySpace;
        $path = "/$kvPath/$keySpace";
        return $client->getDataByPath($path);
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
        $path = "/$kvPath/$keySpace";
        $client->setDataByPath($path, $data);
    }
}

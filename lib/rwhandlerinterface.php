<?php

namespace Bx\HashiCorp;

use Bx\HashiCorp\Client\HashiCorpVaultClientInterface;

interface RWHandlerInterface
{
    public static function getDataFromKeySpace(
        HashiCorpVaultClientInterface $client,
        string $keySpace,
        string $kvPath = 'secret',
        array $keySpaceMap = []
    ): ?array;

    public static function setDataToKeySpace(
        HashiCorpVaultClientInterface $client,
        string $keySpace,
        array $data,
        string $kvPath = 'secret',
        array $keySpaceMap = []
    ): void;
}

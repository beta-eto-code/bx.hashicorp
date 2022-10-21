<?php

namespace Bx\HashiCorp\Client;

interface HashiCorpVaultClientInterface
{
    public function getDataByPath(string $path): ?array;
    public function setDataByPath(string $path, array $data): void;
}

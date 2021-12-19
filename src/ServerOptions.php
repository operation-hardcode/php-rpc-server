<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer;

final class ServerOptions
{
    public const VERSION = '2.0';

    public function __construct(public readonly string $version = self::VERSION)
    {
    }
}

<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer\Protocol\Validation;

use OperationHardcode\PhpRpcServer\RpcServer;

/**
 * @psalm-import-type JSONRPC from RpcServer
 */
interface ValidateRequest
{
    /**
     * @psalm-param JSONRPC $payload
     */
    public function validate(array $payload): bool;
}

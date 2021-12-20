<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer\Protocol\Validation;

use OperationHardcode\PhpRpcServer\RpcServer;

/**
 * @psalm-import-type JSONRPC from RpcServer
 * @psalm-import-type ValidJSONRPC from RpcServer
 */
interface ValidateRequest
{
    /**
     * @psalm-param JSONRPC $payload
     *
     * @psalm-assert-if-true ValidJSONRPC $payload
     */
    public function validate(array $payload): bool;
}

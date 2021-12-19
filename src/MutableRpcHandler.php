<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer;

interface MutableRpcHandler extends RpcHandler
{
    /**
     * @psalm-param callable(RpcRequest, ?RpcResponse): ?RpcResponse $handler
     */
    public function add(string $method, callable $handler): RpcHandler;
}

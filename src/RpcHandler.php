<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer;

interface RpcHandler
{
    /**
     * @psalm-return ($response is null ? null : RpcResponse)
     */
    public function handle(RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse;
}

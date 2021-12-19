<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer;

final class InvokableRpcHandler implements MutableRpcHandler
{
    /**
     * @psalm-param array<string, callable(RpcRequest, ?RpcResponse): ?RpcResponse> $methodsHandlers
     */
    public function __construct(private array $methodsHandlers = [])
    {
    }

    public function handle(RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse
    {
        if (isset($this->methodsHandlers[$request->method])) {
            return $this->methodsHandlers[$request->method]($request, $response);
        }

        return RpcResponse::methodNotFound($request->id);
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $method, callable $handler): RpcHandler
    {
        $this->methodsHandlers[$method] = $handler;

        return $this;
    }
}

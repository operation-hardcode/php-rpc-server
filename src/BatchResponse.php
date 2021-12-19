<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer;

final class BatchResponse
{
    /**
     * @param RpcResponse[] $responses
     */
    public function __construct(private array $responses)
    {
    }

    public function merge(): RpcResponse
    {
        return new RpcResponse(array_map(function (RpcResponse $response): array {
            return $response->toArray();
        }, $this->responses));
    }

    public function isEmpty(): bool
    {
        return count($this->responses) === 0;
    }
}

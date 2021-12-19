<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer;

/**
 * @psalm-import-type ValidJSONRPC from RpcServer
 */
final class RpcRequest
{
    public function __construct(
        public readonly string $method,
        public readonly array|string|int|bool|null $params = null,
        public readonly string|int|null $id = null,
    ) {
    }

    public function isNotification(): bool
    {
        return $this->id === null;
    }

    /**
     * @param ValidJSONRPC $payload
     *
     * @return RpcRequest
     */
    public static function parse(array $payload): RpcRequest
    {
        return new RpcRequest($payload['method'], $payload['params'] ?? null, $payload['id'] ?? null);
    }

    /**
     * @psalm-template T
     *
     * @psalm-param callable(array|int|string|bool): T $transformer
     *
     * @psalm-return T|null
     */
    public function transform(callable $transformer)
    {
        if (!\is_null($this->params)) {
            return $transformer($this->params);
        }

        return null;
    }
}

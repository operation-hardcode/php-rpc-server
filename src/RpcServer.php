<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer;

use OperationHardcode\PhpRpcServer\Protocol\Validation\RequestValidator;
use OperationHardcode\PhpRpcServer\Protocol\Validation\ValidateId;
use OperationHardcode\PhpRpcServer\Protocol\Validation\ValidateMethod;
use OperationHardcode\PhpRpcServer\Protocol\Validation\ValidateRequest;
use OperationHardcode\PhpRpcServer\Protocol\Validation\ValidateVersion;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @psalm-type JSONRPC = array{jsonrpc?: string, method?: string, params?: array|string|int|bool, id?: string|int}
 * @psalm-type BATCH = array<int, JSONRPC>
 * @psalm-type ValidJSONRPC = array{jsonrpc: string, method: string, params?: array|string|int|bool, id?: string|int}
 */
final class RpcServer
{
    private ValidateRequest $validator;

    public function __construct(
        private RpcHandler $rpcHandler,
        private LoggerInterface $logger = new NullLogger(),
    ) {
        $this->validator = new RequestValidator(new ValidateVersion(), new ValidateMethod(), new ValidateId());
    }

    /**
     * @psalm-param array<string, callable(RpcRequest, ?RpcResponse): ?RpcResponse> $methodsHandlers
     */
    public static function new(array $methodsHandlers, LoggerInterface $logger = new NullLogger()): RpcServer
    {
        return new RpcServer(new InvokableRpcHandler($methodsHandlers), $logger);
    }

    public function process(?string $json = null): ?RpcResponse
    {
        if (null === $json) {
            return RpcResponse::parseError();
        }

        /** @psalm-var JSONRPC|BATCH $payload */
        $payload = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return RpcResponse::parseError();
        }

        if (isset($payload[0])) {
            /** @psalm-var BATCH $payload */
            $response = $this->handleBatch($payload);

            if ($response->isEmpty()) {
                // All requests were notifications.
                return null;
            }

            return $response->merge();
        }

        /** @psalm-var JSONRPC $payload */
        if (!$this->validator->validate($payload)) {
            return RpcResponse::invalidRequest($payload['id'] ?? null);
        }

        /** @psalm-var ValidJSONRPC $payload */
        return $this->rpcHandler->handle(
            RpcRequest::parse($payload),
            isset($payload['id']) ? RpcResponse::prepare($payload['id'] ?? null) : null,
        );
    }

    /**
     * @param BATCH $payload
     *
     * @return BatchResponse
     */
    private function handleBatch(array $payload): BatchResponse
    {
        $responses = [];

        foreach ($payload as $payloadItem) {
            if (!$this->validator->validate($payloadItem)) {
                $responses[] = RpcResponse::invalidRequest($payloadItem['id'] ?? null);
            } elseif (!isset($payloadItem['id'])) {
                /** @psalm-var ValidJSONRPC $payloadItem */
                $this->rpcHandler->handle(RpcRequest::parse($payloadItem));
            } else {
                /** @psalm-var ValidJSONRPC $payloadItem */
                $responses[] = $this->rpcHandler->handle(RpcRequest::parse($payloadItem), RpcResponse::prepare($payloadItem['id']));
            }

            $this->logger->debug(
                'The '.(isset($payloadItem['id']) ? "request with id \"{$payloadItem['id']}\" " : 'notification ').'for method "{method}" was handled successful.',
                [
                    'method' => $payloadItem['method']
                ]
            );
        }

        return new BatchResponse($responses);
    }
}

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

    /**
     * @param ValidateRequest[] $validators
     */
    public function __construct(
        private RpcHandler $rpcHandler,
        private LoggerInterface $logger = new NullLogger(),
        array $validators = []
    ) {
        $this->validator = new RequestValidator(new ValidateVersion(), new ValidateMethod(), new ValidateId(), ...$validators);
    }

    /**
     * @psalm-param array<string, callable(RpcRequest, ?RpcResponse): ?RpcResponse> $methodsHandlers
     * @psalm-param ValidateRequest[] $validators
     */
    public static function new(array $methodsHandlers, LoggerInterface $logger = new NullLogger(), array $validators = []): RpcServer
    {
        return new RpcServer(new InvokableRpcHandler($methodsHandlers), $logger, $validators);
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
        return $this->once($payload);
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
            $response = $this->once($payloadItem);

            if (!\is_null($response)) {
                $responses[] = $response;
            }
        }

        return new BatchResponse($responses);
    }

    /**
     * @param JSONRPC $payload
     */
    private function once(array $payload): ?RpcResponse
    {
        if (!$this->validator->validate($payload)) {
            $response = RpcResponse::invalidRequest($payload['id'] ?? null);

            $this->logger->error('The '.(isset($payload['id']) ? "request with id \"{$payload['id']}\" and " : 'notification ').'with params "{params}" for method "{method}" was failed with error code "{code}" and message "{message}".', [
                'method' => $payload['method'] ?? '',
                'params' => json_encode($payload['params'] ?? []),
                'code' => $response->errorCode(),
                'message' => $response->errorMessage(),
            ]);

            return $response;
        }

        $request = RpcRequest::parse($payload);

        $response = $this->rpcHandler->handle(
            $request,
            !$request->isNotification() ? RpcResponse::prepare($payload['id']) : null
        );

        if ($response?->isErroneous() === true) {
            $this->logger->error('The '.(isset($payload['id']) ? "request with id \"{$payload['id']}\" and " : 'notification ').'with params "{params}" for method "{method}" was failed with error code "{code}", message "{message}" and exception "{exception}".', [
                'method' => $payload['method'],
                'params' => json_encode($payload['params'] ?? []),
                'code' => $response?->errorCode(),
                'message' => $response?->errorMessage(),
                'exception' => $response->exception()?->getMessage(),
                'trace' => $response->exception(),
            ]);
        }

        return $response;
    }
}

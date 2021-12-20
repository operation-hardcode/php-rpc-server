<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer;

use JetBrains\PhpStorm\Internal\TentativeType;
use OperationHardcode\PhpRpcServer\Protocol\ErrorCode;
use OperationHardcode\PhpRpcServer\Protocol\Version;

final class RpcResponse implements \JsonSerializable
{
    private ?\Throwable $exception = null;
    private ?int $errorCode = null;
    private ?string $errorMessage = null;

    public function __construct(private array $response = [])
    {
    }

    public static function prepare(string|int|null $id = null): RpcResponse
    {
        return new RpcResponse(['jsonrpc' => Version::TWO->value, 'result' => null, 'id' => $id]);
    }

    public function addResult(mixed $result): RpcResponse
    {
        $response = clone $this;

        $response->response['result'] = $result;

        return $response;
    }

    public function addError(int $code, string $message, mixed $data = null): RpcResponse
    {
        $response = clone $this;

        $response->errorCode = $code;
        $response->errorMessage = $message;
        $response->response['error'] = array_filter([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ]);

        return $response;
    }

    public static function parseError(): RpcResponse
    {
        return self::withError(ErrorCode::PARSE_ERROR);
    }

    public static function internalError(string|int|null $id = null, ?\Throwable $e = null): RpcResponse
    {
        $response = self::withError(ErrorCode::INTERNAL_ERROR, $id);
        $response->exception = $e;

        return $response;
    }

    public static function methodNotFound(string|int|null $id = null): RpcResponse
    {
        return self::withError(ErrorCode::METHOD_NOT_FOUND, $id);
    }

    public static function invalidRequest(mixed $id = null): RpcResponse
    {
        $idForResponse = (is_int($id) || is_string($id) || is_null($id)) ? $id : null;

        return self::withError(ErrorCode::INVALID_REQUEST, $idForResponse);
    }

    private static function withError(ErrorCode $errorCode, string|int|null $id = null): RpcResponse
    {
        $response = new RpcResponse([
            'jsonrpc' => Version::TWO->value,
            'error' => [
                'code' => $errorCode->value,
                'message' => $errorCode->interpret(),
            ],
            'id' => $id,
        ]);

        $response->errorCode = $errorCode->value;
        $response->errorMessage = $errorCode->interpret();

        return $response;
    }

    public function toArray(): array
    {
        return $this->response;
    }

    public function jsonSerialize(): array
    {
        return $this->response;
    }
}

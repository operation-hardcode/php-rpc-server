<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer\Tests;

use OperationHardcode\PhpRpcServer\Protocol\ErrorCode;
use OperationHardcode\PhpRpcServer\Protocol\Version;
use OperationHardcode\PhpRpcServer\RpcRequest;
use OperationHardcode\PhpRpcServer\RpcResponse;
use PHPUnit\Framework\TestCase;

final class RpcResponseTest extends TestCase
{
    public function testMethodNotFoundResponse(): void
    {
        $response = RpcResponse::methodNotFound();
        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => ErrorCode::METHOD_NOT_FOUND->value,
                    'message' => ErrorCode::METHOD_NOT_FOUND->interpret(),
                ],
                'id' => null,
            ],
            $response->toArray()
        );

        $response = RpcResponse::methodNotFound(1);

        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => ErrorCode::METHOD_NOT_FOUND->value,
                    'message' => ErrorCode::METHOD_NOT_FOUND->interpret(),
                ],
                'id' => 1,
            ],
            $response->toArray()
        );
    }

    public function testParseErrorResponse(): void
    {
        $response = RpcResponse::parseError();

        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => ErrorCode::PARSE_ERROR->value,
                    'message' => ErrorCode::PARSE_ERROR->interpret(),
                ],
                'id' => null,
            ],
            $response->toArray()
        );
    }

    public function testInvalidRequest(): void
    {
        $response = RpcResponse::invalidRequest();

        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => ErrorCode::INVALID_REQUEST->value,
                    'message' => ErrorCode::INVALID_REQUEST->interpret(),
                ],
                'id' => null,
            ],
            $response->toArray()
        );

        $response = RpcResponse::invalidRequest(1);

        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => ErrorCode::INVALID_REQUEST->value,
                    'message' => ErrorCode::INVALID_REQUEST->interpret(),
                ],
                'id' => 1,
            ],
            $response->toArray()
        );
    }

    public function testInternalError(): void
    {
        $response = RpcResponse::internalError();

        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => ErrorCode::INTERNAL_ERROR->value,
                    'message' => ErrorCode::INTERNAL_ERROR->interpret(),
                ],
                'id' => null,
            ],
            $response->toArray()
        );

        $response = RpcResponse::internalError(1);

        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => ErrorCode::INTERNAL_ERROR->value,
                    'message' => ErrorCode::INTERNAL_ERROR->interpret(),
                ],
                'id' => 1,
            ],
            $response->toArray()
        );
    }

    public function testInvalidParams(): void
    {
        $response = RpcResponse::invalidParams();

        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => ErrorCode::INVALID_PARAMS->value,
                    'message' => ErrorCode::INVALID_PARAMS->interpret(),
                ],
                'id' => null,
            ],
            $response->toArray()
        );

        $response = RpcResponse::invalidParams(1);

        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => ErrorCode::INVALID_PARAMS->value,
                    'message' => ErrorCode::INVALID_PARAMS->interpret(),
                ],
                'id' => 1,
            ],
            $response->toArray()
        );

        $response = RpcResponse::invalidParams(1, ['name' => 'Must be an string.']);

        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => ErrorCode::INVALID_PARAMS->value,
                    'message' => ErrorCode::INVALID_PARAMS->interpret(),
                    'data' => [
                        'name' => 'Must be an string.'
                    ],
                ],
                'id' => 1,
            ],
            $response->toArray()
        );
    }

    public function testCustomErrors(): void
    {
        $response = RpcResponse::prepare(RpcRequest::parse(['id' => 1, 'method' => 'messages.get']));
        $response = $response->addError(-1000, 'No access');

        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => -1000,
                    'message' => 'No access',
                ],
                'id' => 1,
            ],
            $response->toArray()
        );

        $response = RpcResponse::prepare(RpcRequest::parse(['id' => 1, 'method' => 'messages.get']));
        $response = $response->addError(-1001, 'Validation errors', ['email' => 'The email already exists.']);

        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => -1001,
                    'message' => 'Validation errors',
                    'data' => [
                        'email' => 'The email already exists.'
                    ],
                ],
                'id' => 1,
            ],
            $response->toArray()
        );
        self::assertTrue($response->isErroneous());
    }

    public function testSuccessResult(): void
    {
        $response = RpcResponse::prepare(RpcRequest::parse(['id' => 1, 'method' => 'messages.get']));

        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'result' => null,
                'id' => 1,
            ],
            $response->toArray()
        );

        $response = RpcResponse::prepare(RpcRequest::parse(['id' => 1, 'method' => 'messages.get']));
        $response = $response->addResult(19);

        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'result' => 19,
                'id' => 1,
            ],
            $response->toArray()
        );
    }
}

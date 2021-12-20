<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer\Tests;

use OperationHardcode\PhpRpcServer\InvokableRpcHandler;
use OperationHardcode\PhpRpcServer\Protocol\ErrorCode;
use OperationHardcode\PhpRpcServer\Protocol\Version;
use OperationHardcode\PhpRpcServer\RpcRequest;
use OperationHardcode\PhpRpcServer\RpcResponse;
use PHPUnit\Framework\TestCase;

final class InvokableRpcHandlerTest extends TestCase
{
    public function testSuccessMessageHandled(): void
    {
        $handler = new InvokableRpcHandler([
            'messages.get' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
                return $response?->addResult(10);
            }
        ]);

        $request = RpcRequest::parse(['id' => 1, 'method' => 'messages.get']);

        $response = $handler->handle($request, RpcResponse::prepare($request));

        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'result' => 10,
                'id' => 1,
            ],
            $response->toArray(),
        );
    }

    public function testMethodNotFound(): void
    {
        $handler = new InvokableRpcHandler();

        $request = RpcRequest::parse(['id' => 1, 'method' => 'messages.get']);

        $response = $handler->handle($request, RpcResponse::prepare($request));

        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => ErrorCode::METHOD_NOT_FOUND->value,
                    'message' => ErrorCode::METHOD_NOT_FOUND->interpret(),
                ],
                'id' => 1,
            ],
            $response->toArray(),
        );
    }

    public function testInternalError(): void
    {
        $handler = new InvokableRpcHandler([
            'messages.get' => function (RpcRequest $_request, ?RpcResponse $_response = null): RpcResponse {
                throw new \InvalidArgumentException();
            }
        ]);

        $request = RpcRequest::parse(['id' => 1, 'method' => 'messages.get']);

        $response = $handler->handle($request, RpcResponse::prepare($request));

        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => ErrorCode::INTERNAL_ERROR->value,
                    'message' => ErrorCode::INTERNAL_ERROR->interpret(),
                ],
                'id' => 1,
            ],
            $response->toArray(),
        );
    }

    public function testCustomErrors(): void
    {
        $handler = new InvokableRpcHandler([
            'messages.get' => function (RpcRequest $_request, ?RpcResponse $response = null): RpcResponse {
                return $response?->addError(-1000, 'Validation errors', [
                    'email' => 'The email already exists.',
                ]);
            }
        ]);

        $request = RpcRequest::parse(['id' => 1, 'method' => 'messages.get']);

        $response = $handler->handle($request, RpcResponse::prepare($request));

        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => -1000,
                    'message' => 'Validation errors',
                    'data' => [
                        'email' => 'The email already exists.',
                    ],
                ],
                'id' => 1,
            ],
            $response->toArray(),
        );
    }

    public function testNotification(): void
    {
        $handler = new InvokableRpcHandler([
            'messages.get' => function (RpcRequest $_request, ?RpcResponse $response = null): ?RpcResponse {
                return $response;
            }
        ]);

        $request = RpcRequest::parse(['id' => 1, 'method' => 'messages.get']);

        self::assertNull($handler->handle($request));
    }
}

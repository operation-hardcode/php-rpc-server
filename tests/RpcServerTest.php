<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer\Tests;

use OperationHardcode\PhpRpcServer\Protocol\ErrorCode;
use OperationHardcode\PhpRpcServer\Protocol\Validation\ValidateRequest;
use OperationHardcode\PhpRpcServer\Protocol\Version;
use OperationHardcode\PhpRpcServer\RpcRequest;
use OperationHardcode\PhpRpcServer\RpcResponse;
use OperationHardcode\PhpRpcServer\RpcServer;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class RpcServerTest extends TestCase
{
    public function testCustomValidators(): void
    {
        $server = RpcServer::new(
            methodsHandlers: [
                'messages.get' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
                    return $response;
                }
            ],
            logger: new NullLogger(),
            validators: [
                new DenyNotAllowedMethods(['messages.get'])
            ]
        );

        $response = $server->process('{"jsonrpc": "2.0", "method": "messages.get", "id": 1}');
        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => ErrorCode::INVALID_REQUEST->value,
                    'message' => ErrorCode::INVALID_REQUEST->interpret(),
                ],
                'id' => 1,
            ],
            $response->toArray(),
        );
    }

    public function testSingleMethodSuccess(): void
    {
        $server = RpcServer::new(
            [
                'messages.get' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
                    return $response?->addResult(10);
                }
            ],
        );

        $response = $server->process('{"jsonrpc": "2.0", "method": "messages.get", "id": 1}');
        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'result' => 10,
                'id' => 1,
            ],
            $response->toArray(),
        );
    }

    public function testSingleMethodError(): void
    {
        $server = RpcServer::new(
            [
                'messages.get' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
                    return $response?->addError(-1100, 'Access denied');
                }
            ],
        );

        $response = $server->process('{"jsonrpc": "2.0", "method": "messages.get", "id": 1}');
        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => -1100,
                    'message' => 'Access denied',
                ],
                'id' => 1,
            ],
            $response->toArray()
        );
    }

    public function testSingleNotification(): void
    {
        $server = RpcServer::new(
            [
                'messages.get' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
                    return $response?->addResult(10);
                }
            ],
        );

        $response = $server->process('{"jsonrpc": "2.0", "method": "messages.get"}');
        self::assertNull($response);
    }

    public function testSingleNotificationError(): void
    {
        $server = RpcServer::new(
            [
                'messages.get' => function (RpcRequest $_request, ?RpcResponse $_response = null): ?RpcResponse {
                    throw new \InvalidArgumentException();
                }
            ],
        );

        $response = $server->process('{"jsonrpc": "2.0", "method": "messages.get"}');
        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => ErrorCode::INTERNAL_ERROR->value,
                    'message' => ErrorCode::INTERNAL_ERROR->interpret(),
                ],
                'id' => null,
            ],
            $response->toArray(),
        );
    }

    public function testParseError(): void
    {
        $server = RpcServer::new([]);

        $response = $server->process();
        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => ErrorCode::PARSE_ERROR->value,
                    'message' => ErrorCode::PARSE_ERROR->interpret(),
                ],
                'id' => null,
            ],
            $response->toArray(),
        );

        $response = $server->process('{');
        self::assertEquals(
            [
                'jsonrpc' => Version::TWO->value,
                'error' => [
                    'code' => ErrorCode::PARSE_ERROR->value,
                    'message' => ErrorCode::PARSE_ERROR->interpret(),
                ],
                'id' => null,
            ],
            $response->toArray(),
        );
    }

    public function testAllRequestsInBatchSucceeded(): void
    {
        $server = RpcServer::new([
            'messages.get' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
                return $response?->addResult([10, 20]);
            },
            'users.get' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
                return $response?->addResult([30, 40]);
            },
            'orders.create' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
                return $response;
            },
        ]);

        $response = $server->process(
            '[{"jsonrpc": "2.0", "method": "messages.get", "id": 1}, {"jsonrpc": "2.0", "method": "users.get", "id": 2}, {"jsonrpc": "2.0", "method": "orders.create", "id": 3}]'
        );

        self::assertEquals(
            [
                [
                    'jsonrpc' => Version::TWO->value,
                    'result' => [10, 20],
                    'id' => 1,
                ],
                [
                    'jsonrpc' => Version::TWO->value,
                    'result' => [30, 40],
                    'id' => 2,
                ],
                [
                    'jsonrpc' => Version::TWO->value,
                    'result' => null,
                    'id' => 3,
                ],
            ],
            $response->toArray()
        );
    }

    public function testAllRequestsAndNotificationsInBatchSucceeded(): void
    {
        $server = RpcServer::new([
            'messages.get' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
                return $response?->addResult([10, 20]);
            },
            'users.get' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
                return $response?->addResult([30, 40]);
            },
            'orders.create' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
                return $response;
            },
            'users.notify' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
                return $response;
            },
        ]);

        $response = $server->process(
            '[{"jsonrpc": "2.0", "method": "messages.get", "id": 1}, {"jsonrpc": "2.0", "method": "users.get", "id": 2}, {"jsonrpc": "2.0", "method": "orders.create", "id": 3}, {"jsonrpc": "2.0", "method": "users.notify"}]'
        );

        self::assertEquals(
            [
                [
                    'jsonrpc' => Version::TWO->value,
                    'result' => [10, 20],
                    'id' => 1,
                ],
                [
                    'jsonrpc' => Version::TWO->value,
                    'result' => [30, 40],
                    'id' => 2,
                ],
                [
                    'jsonrpc' => Version::TWO->value,
                    'result' => null,
                    'id' => 3,
                ],
            ],
            $response->toArray()
        );
    }

    public function testSomeRequestsInBatchFails(): void
    {
        $server = RpcServer::new([
            'messages.get' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
                return $response?->addResult([10, 20]);
            },
            'users.get' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
                throw new \InvalidArgumentException();
            },
            'orders.create' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
                return $response;
            },
        ]);

        $response = $server->process(
            '[{"jsonrpc": "2.0", "method": "messages.get", "id": 1}, {"jsonrpc": "2.0", "method": "users.get", "id": 2}, {"jsonrpc": "2.0", "method": "orders.create", "id": 3}, {"jsonrpc": "2.0", "method": "users.notify"}]'
        );

        self::assertEquals(
            [
                [
                    'jsonrpc' => Version::TWO->value,
                    'result' => [10, 20],
                    'id' => 1,
                ],
                [
                    'jsonrpc' => Version::TWO->value,
                    'error' => [
                        'code' => ErrorCode::INTERNAL_ERROR->value,
                        'message' => ErrorCode::INTERNAL_ERROR->interpret(),
                    ],
                    'id' => 2,
                ],
                [
                    'jsonrpc' => Version::TWO->value,
                    'result' => null,
                    'id' => 3,
                ],
                [
                    'jsonrpc' => Version::TWO->value,
                    'error' => [
                        'code' => ErrorCode::METHOD_NOT_FOUND->value,
                        'message' => ErrorCode::METHOD_NOT_FOUND->interpret(),
                    ],
                    'id' => null,
                ],
            ],
            $response->toArray()
        );
    }

    public function testSomeRequestsAndNotificationsInBatchFails(): void
    {
        $server = RpcServer::new([
            'messages.get' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
                return $response?->addResult([10, 20]);
            },
            'users.get' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
                throw new \InvalidArgumentException();
            },
            'orders.create' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
                return $response;
            },
            'users.notify' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
                return $response;
            },
        ]);

        $response = $server->process(
            '[{"jsonrpc": "2.0", "method": "messages.get", "id": 1}, {"jsonrpc": "2.0", "method": "users.get", "id": 2}, {"jsonrpc": "2.0", "method": "orders.create", "id": 3}, {"jsonrpc": "2.0", "method": "users.notify"}, {"jsonrpc": "2.0", "method": "customers.notify"}]'
        );

        self::assertEquals(
            [
                [
                    'jsonrpc' => Version::TWO->value,
                    'result' => [10, 20],
                    'id' => 1,
                ],
                [
                    'jsonrpc' => Version::TWO->value,
                    'error' => [
                        'code' => ErrorCode::INTERNAL_ERROR->value,
                        'message' => ErrorCode::INTERNAL_ERROR->interpret(),
                    ],
                    'id' => 2,
                ],
                [
                    'jsonrpc' => Version::TWO->value,
                    'result' => null,
                    'id' => 3,
                ],
                [
                    'jsonrpc' => Version::TWO->value,
                    'error' => [
                        'code' => ErrorCode::METHOD_NOT_FOUND->value,
                        'message' => ErrorCode::METHOD_NOT_FOUND->interpret(),
                    ],
                    'id' => null,
                ],
            ],
            $response->toArray()
        );
    }
}

final class DenyNotAllowedMethods implements ValidateRequest
{
    public function __construct(private array $methods)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $payload): bool
    {
        return isset($payload['method']) && !\in_array($payload['method'], $this->methods);
    }
}

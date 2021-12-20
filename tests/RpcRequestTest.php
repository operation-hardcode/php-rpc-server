<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer\Tests;

use OperationHardcode\PhpRpcServer\RpcRequest;
use PHPUnit\Framework\TestCase;

final class RpcRequestTest extends TestCase
{
    public function testRequestIsNotification(): void
    {
        $request = RpcRequest::parse(['method' => 'messages.get']);
        self::assertTrue($request->isNotification());
        self::assertEquals('messages.get', $request->method);
        self::assertNull($request->params);
    }

    public function testNormalRequest(): void
    {
        $request = RpcRequest::parse(['method' => 'messages.get', 'id' => 1]);
        self::assertFalse($request->isNotification());
        self::assertEquals('messages.get', $request->method);
        self::assertEquals(1, $request->id);
        self::assertNull($request->params);

        $request = RpcRequest::parse(['method' => 'messages.get', 'id' => 1, 'params' => [1, 2]]);
        self::assertFalse($request->isNotification());
        self::assertEquals('messages.get', $request->method);
        self::assertEquals(1, $request->id);
        self::assertEquals([1, 2], $request->params);
    }
}

<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer\Tests;

use OperationHardcode\PhpRpcServer\BatchResponse;
use OperationHardcode\PhpRpcServer\Protocol\ErrorCode;
use OperationHardcode\PhpRpcServer\Protocol\Version;
use OperationHardcode\PhpRpcServer\RpcResponse;
use PHPUnit\Framework\TestCase;

final class BatchResponseTest extends TestCase
{
    public function testBatchResponseEmpty(): void
    {
        self::assertTrue((new BatchResponse([]))->isEmpty());
    }

    public function testBatchResponseIsNotEmpty(): void
    {
        self::assertFalse((new BatchResponse([RpcResponse::parseError()]))->isEmpty());
    }

    public function testMergedBatchResponse(): void
    {
        $batch = new BatchResponse([
            RpcResponse::parseError(),
            RpcResponse::methodNotFound(1)
        ]);

        self::assertEquals(
            [
                [
                    'jsonrpc' => Version::TWO->value,
                    'error' => [
                        'code' => ErrorCode::PARSE_ERROR->value,
                        'message' => ErrorCode::PARSE_ERROR->interpret(),
                    ],
                    'id' => null,
                ],
                [
                    'jsonrpc' => Version::TWO->value,
                    'error' => [
                        'code' => ErrorCode::METHOD_NOT_FOUND->value,
                        'message' => ErrorCode::METHOD_NOT_FOUND->interpret(),
                    ],
                    'id' => 1,
                ]
            ],
            $batch->merge()->toArray()
        );
    }
}

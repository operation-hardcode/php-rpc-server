<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer\Tests;

use OperationHardcode\PhpRpcServer\Protocol\Validation\RequestValidator;
use OperationHardcode\PhpRpcServer\Protocol\Validation\ValidateId;
use OperationHardcode\PhpRpcServer\Protocol\Validation\ValidateMethod;
use OperationHardcode\PhpRpcServer\Protocol\Validation\ValidateVersion;
use OperationHardcode\PhpRpcServer\Protocol\Version;
use PHPUnit\Framework\TestCase;

final class ValidateRequestTest extends TestCase
{
    public function testValidateId(): void
    {
        $validator = new ValidateId();
        self::assertTrue($validator->validate(['id' => 1]));
        self::assertTrue($validator->validate(['id' => '1']));
        self::assertTrue($validator->validate(['id' => null]));
        self::assertTrue($validator->validate([]));
        self::assertFalse($validator->validate(['id' => false]));
        self::assertFalse($validator->validate(['id' => 1.2]));
        self::assertFalse($validator->validate(['id' => []]));
    }

    public function testValidateMethod(): void
    {
        $validator = new ValidateMethod();
        self::assertTrue($validator->validate(['method' => 'messages.get']));
        self::assertFalse($validator->validate([]));
        self::assertFalse($validator->validate(['method' => 1]));
        self::assertFalse($validator->validate(['method' => null]));
    }

    public function testValidateVersion(): void
    {
        $validator = new ValidateVersion();
        self::assertTrue($validator->validate(['jsonrpc' => Version::TWO->value]));
        self::assertFalse($validator->validate([]));
        self::assertFalse($validator->validate(['jsonrpc' => '1.0']));
    }

    public function testRequestValidator(): void
    {
        $validator = new RequestValidator(
            new ValidateVersion(),
            new ValidateMethod(),
            new ValidateId(),
        );

        self::assertTrue($validator->validate(['jsonrpc' => Version::TWO->value, 'method' => 'messages.get', 'id' => 1]));
        self::assertFalse($validator->validate([]));
    }
}

<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer\Protocol\Validation;

use OperationHardcode\PhpRpcServer\Protocol\Version;

final class ValidateVersion implements ValidateRequest
{
    /**
     * {@inheritdoc}
     */
    public function validate(array $payload): bool
    {
        return isset($payload['jsonrpc']) && $payload['jsonrpc'] === Version::TWO->value;
    }
}

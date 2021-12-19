<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer\Protocol\Validation;

final class ValidateMethod implements ValidateRequest
{
    /**
     * {@inheritdoc}
     */
    public function validate(array $payload): bool
    {
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        return isset($payload['method']) && \is_string($payload['method']);
    }
}

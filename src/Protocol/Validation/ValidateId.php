<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer\Protocol\Validation;

final class ValidateId implements ValidateRequest
{
    /**
     * {@inheritdoc}
     */
    public function validate(array $payload): bool
    {
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        return !isset($payload['id']) || \is_string($payload['id']) || \is_int($payload['id']);
    }
}

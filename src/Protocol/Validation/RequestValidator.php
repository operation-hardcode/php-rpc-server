<?php

declare(strict_types=1);

namespace OperationHardcode\PhpRpcServer\Protocol\Validation;

final class RequestValidator implements ValidateRequest
{
    /**
     * @var ValidateRequest[]
     */
    private array $validators;

    public function __construct(ValidateRequest ...$validators)
    {
        $this->validators = $validators;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $payload): bool
    {
        foreach ($this->validators as $validator) {
            if ($validator->validate($payload) === false) {
                return false;
            }
        }

        return true;
    }
}

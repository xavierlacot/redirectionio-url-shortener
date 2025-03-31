<?php

namespace App\Validator;

use App\RedirectionIoClient;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class ShortCodeValidator extends ConstraintValidator
{
    public function __construct(
        private readonly RedirectionIoClient $redirectionIoClient,
    ) {
    }

    /**
     * @param ShortCode $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!$this->redirectionIoClient->shortCodeExists($value)) {
            return;
        }

        // TODO: implement the validation here
        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation()
        ;
    }
}

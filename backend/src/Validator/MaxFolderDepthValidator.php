<?php

namespace App\Validator;

use App\Entity\Folder;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class MaxFolderDepthValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MaxFolderDepth) {
            throw new UnexpectedTypeException($constraint, MaxFolderDepth::class);
        }

        if (!$value instanceof Folder) {
            throw new UnexpectedTypeException($value, Folder::class);
        }

        $depth = $this->calculateDepth($value);

        if ($depth > $constraint->max) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ max }}', (string) $constraint->max)
                ->addViolation();
        }
    }

    private function calculateDepth(Folder $folder): int
    {
        $depth = 1;
        $current = $folder->getParent();

        while ($current !== null) {
            $depth++;
            $current = $current->getParent();
        }

        return $depth;
    }
}

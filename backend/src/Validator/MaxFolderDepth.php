<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class MaxFolderDepth extends Constraint
{
    public string $message = 'Максимальная глубина вложенности папок - {{ max }} уровня';
    public int $max = 3;

    public function __construct(
        int $max = 3,
        array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct([], $groups, $payload);
        $this->max = $max;
    }

    public function getDefaultOption(): ?string
    {
        return 'max';
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

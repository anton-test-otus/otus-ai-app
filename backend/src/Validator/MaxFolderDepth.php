<?php

namespace App\Validator;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class MaxFolderDepth extends Constraint
{
    #[HasNamedArguments]
    public function __construct(
        public int $max = 3,
        public string $message = 'Максимальная глубина вложенности папок - {{ max }} уровня',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordDto
{
    #[Assert\NotBlank(message: 'Текущий пароль обязателен')]
    public ?string $currentPassword = null;

    #[Assert\NotBlank(message: 'Новый пароль обязателен')]
    #[Assert\Length(min: 6, minMessage: 'Пароль должен содержать минимум {{ limit }} символов')]
    #[Assert\NotEqualTo(
        propertyPath: 'currentPassword',
        message: 'Новый пароль должен отличаться от текущего',
    )]
    public ?string $newPassword = null;
}

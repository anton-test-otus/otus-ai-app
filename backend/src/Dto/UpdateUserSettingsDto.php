<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserSettingsDto
{
    public const ALLOWED_AUTOSAVE_DELAY_SECONDS = [5, 10, 15, 30, 60];

    public const ALLOWED_VERSION_CONSOLIDATION_WINDOW_MINUTES = [1, 2, 3, 4, 5, 10, 15, 30, 60];

    #[Assert\Choice(
        choices: self::ALLOWED_AUTOSAVE_DELAY_SECONDS,
        message: 'Допустимые значения задержки автосохранения: 5, 10, 15, 30, 60 секунд',
    )]
    public ?int $autosaveDelaySeconds = null;

    #[Assert\Choice(
        choices: self::ALLOWED_VERSION_CONSOLIDATION_WINDOW_MINUTES,
        message: 'Допустимые значения окна версионирования: 1, 2, 3, 4, 5, 10, 15, 30, 60 минут',
    )]
    public ?int $versionConsolidationWindowMinutes = null;
}

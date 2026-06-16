<?php

namespace App\Settings;

final class UserSettingOptions
{
    public const ALLOWED_AUTOSAVE_DELAY_SECONDS = [5, 10, 15, 30, 60];

    public const ALLOWED_VERSION_CONSOLIDATION_WINDOW_MINUTES = [1, 2, 3, 4, 5, 10, 15, 30, 60];

    private function __construct()
    {
    }
}

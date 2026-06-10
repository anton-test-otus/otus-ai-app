<?php

namespace App\Service;

use App\Entity\User;

class UserSettingsResolver
{
    public function __construct(
        private int $defaultAutosaveDelaySeconds,
        private int $defaultVersionConsolidationWindowMinutes,
    ) {
        if ($this->defaultAutosaveDelaySeconds < 1) {
            $this->defaultAutosaveDelaySeconds = 10;
        }
        if ($this->defaultVersionConsolidationWindowMinutes < 1) {
            $this->defaultVersionConsolidationWindowMinutes = 5;
        }
    }

    public function resolveAutosaveDelaySeconds(User $user): int
    {
        return $user->getAutosaveDelaySeconds() ?? $this->defaultAutosaveDelaySeconds;
    }

    public function resolveVersionConsolidationWindowMinutes(User $user): int
    {
        return $user->getVersionConsolidationWindowMinutes() ?? $this->defaultVersionConsolidationWindowMinutes;
    }

    public function getDefaults(): array
    {
        return [
            'autosaveDelaySeconds' => $this->defaultAutosaveDelaySeconds,
            'versionConsolidationWindowMinutes' => $this->defaultVersionConsolidationWindowMinutes,
        ];
    }

    public function getSettingsForUser(User $user): array
    {
        return [
            'autosaveDelaySeconds' => $user->getAutosaveDelaySeconds(),
            'versionConsolidationWindowMinutes' => $user->getVersionConsolidationWindowMinutes(),
        ];
    }
}

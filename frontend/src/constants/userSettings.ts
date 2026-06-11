/** Соответствует NoteVersionService::MAX_VERSIONS на бэкенде */
export const MAX_NOTE_VERSIONS_PER_NOTE = 50

export const AUTOSAVE_DELAY_SECONDS_OPTIONS = [5, 10, 15, 30, 60] as const

export const VERSION_CONSOLIDATION_MINUTES_OPTIONS = [1, 2, 3, 4, 5, 10, 15, 30, 60] as const

export type AutosaveDelaySeconds = (typeof AUTOSAVE_DELAY_SECONDS_OPTIONS)[number]

export type VersionConsolidationMinutes = (typeof VERSION_CONSOLIDATION_MINUTES_OPTIONS)[number]

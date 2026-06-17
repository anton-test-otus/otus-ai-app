function parsePositiveInt(value: string | undefined, fallback: number): number {
  const parsed = Number(value)
  return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback
}

function parseBoolean(value: string | undefined, fallback: boolean): boolean {
  if (value === undefined || value === '') {
    return fallback
  }
  return value === 'true' || value === '1'
}

export const appConfig = {
  authEnabled: parseBoolean(import.meta.env.VITE_AUTH_ENABLED, true),
  autosaveDelaySeconds: parsePositiveInt(import.meta.env.VITE_AUTOSAVE_DELAY_SECONDS, 10),
  versionConsolidationWindowMinutes: parsePositiveInt(
    import.meta.env.VITE_VERSION_CONSOLIDATION_WINDOW_MINUTES,
    5,
  ),
} as const

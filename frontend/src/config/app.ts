function parsePositiveInt(value: string | undefined, fallback: number): number {
  const parsed = Number(value)
  return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback
}

export const appConfig = {
  autosaveDelaySeconds: parsePositiveInt(import.meta.env.VITE_AUTOSAVE_DELAY_SECONDS, 10),
  versionConsolidationWindowMinutes: parsePositiveInt(
    import.meta.env.VITE_VERSION_CONSOLIDATION_WINDOW_MINUTES,
    5,
  ),
} as const

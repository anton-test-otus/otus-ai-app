function parsePositiveInt(value: string | undefined, fallback: number): number {
  const parsed = Number(value)
  return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback
}

export const appConfig = {
  autosaveDebounceMs: parsePositiveInt(import.meta.env.VITE_AUTOSAVE_DEBOUNCE_MS, 2000),
} as const

/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_API_URL: string
  readonly VITE_AUTOSAVE_DELAY_SECONDS?: string
  readonly VITE_VERSION_CONSOLIDATION_WINDOW_MINUTES?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}

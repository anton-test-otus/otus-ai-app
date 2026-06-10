import { inject, type ComputedRef, type InjectionKey } from 'vue'

export interface LayoutPanelsContext {
  showNavToggle: ComputedRef<boolean>
  openNavigation: () => void
}

export const LAYOUT_PANELS_KEY: InjectionKey<LayoutPanelsContext> = Symbol('layoutPanels')

export function useLayoutPanels() {
  return inject(LAYOUT_PANELS_KEY, null)
}

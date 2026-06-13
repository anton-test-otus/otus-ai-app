import { BREAKPOINTS } from '@/composables/useBreakpoints'

export const MODAL_WIDTH = {
  sm: { width: '90vw', maxWidth: '25rem' },
  md: { width: '90vw', maxWidth: '30rem' },
  lg: { width: '90vw', maxWidth: '50rem' },
  xl: { width: '90vw', maxWidth: '75rem' },
} as const

export const DRAWER_WIDTH = {
  sidebar: { width: '85vw', maxWidth: '400px' },
} as const

export const MODAL_FULLSCREEN_MOBILE_CLASS = 'modal-fullscreen-mobile'

export const MODAL_FULLSCREEN_MOBILE_BREAKPOINTS = {
  [`${BREAKPOINTS.md}px`]: '100vw',
} as const

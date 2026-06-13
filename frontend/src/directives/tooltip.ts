import PrimeTooltip from 'primevue/tooltip'

const VIEWPORT_PADDING = 12

function clampTooltipToViewport(tooltipEl: HTMLElement): void {
  const rect = tooltipEl.getBoundingClientRect()
  let dx = 0
  let dy = 0

  if (rect.left < VIEWPORT_PADDING) {
    dx = VIEWPORT_PADDING - rect.left
  } else if (rect.right > window.innerWidth - VIEWPORT_PADDING) {
    dx = window.innerWidth - VIEWPORT_PADDING - rect.right
  }

  if (rect.top < VIEWPORT_PADDING) {
    dy = VIEWPORT_PADDING - rect.top
  } else if (rect.bottom > window.innerHeight - VIEWPORT_PADDING) {
    dy = window.innerHeight - VIEWPORT_PADDING - rect.bottom
  }

  if (dx === 0 && dy === 0) return

  const left = parseFloat(tooltipEl.style.left)
  const top = parseFloat(tooltipEl.style.top)

  if (!Number.isNaN(left)) tooltipEl.style.left = `${left + dx}px`
  if (!Number.isNaN(top)) tooltipEl.style.top = `${top + dy}px`
}

type TooltipHost = HTMLElement & { $_ptooltipModifiers?: Record<string, boolean> }

type TooltipAlignContext = {
  alignTop: (el: TooltipHost) => void
  alignBottom: (el: TooltipHost) => void
  alignLeft: (el: TooltipHost) => void
  alignRight: (el: TooltipHost) => void
  isOutOfBounds: (el: TooltipHost) => boolean
  getTooltipElement: (el: TooltipHost) => HTMLElement | null
}

export const Tooltip = PrimeTooltip.extend('tooltip', {
  methods: {
    align(this: TooltipAlignContext, el: TooltipHost) {
      const modifiers = el.$_ptooltipModifiers ?? {}

      if (modifiers.top) {
        this.alignTop(el)
        if (this.isOutOfBounds(el)) {
          this.alignBottom(el)
          if (this.isOutOfBounds(el)) {
            this.alignTop(el)
          }
        }
      } else if (modifiers.left) {
        this.alignLeft(el)
        if (this.isOutOfBounds(el)) {
          this.alignRight(el)
          if (this.isOutOfBounds(el)) {
            this.alignTop(el)
            if (this.isOutOfBounds(el)) {
              this.alignBottom(el)
              if (this.isOutOfBounds(el)) {
                this.alignLeft(el)
              }
            }
          }
        }
      } else if (modifiers.bottom) {
        this.alignBottom(el)
        if (this.isOutOfBounds(el)) {
          this.alignTop(el)
          if (this.isOutOfBounds(el)) {
            this.alignBottom(el)
          }
        }
      } else {
        this.alignRight(el)
        if (this.isOutOfBounds(el)) {
          this.alignLeft(el)
          if (this.isOutOfBounds(el)) {
            this.alignTop(el)
            if (this.isOutOfBounds(el)) {
              this.alignBottom(el)
              if (this.isOutOfBounds(el)) {
                this.alignRight(el)
              }
            }
          }
        }
      }

      const tooltipElement = this.getTooltipElement(el)
      if (tooltipElement) {
        clampTooltipToViewport(tooltipElement)
      }
    },
  },
})

export function isModKey(event: KeyboardEvent): boolean {
  return event.ctrlKey || event.metaKey
}

export function isEditableTarget(target: EventTarget | null): boolean {
  if (!(target instanceof HTMLElement)) {
    return false
  }

  if (target.isContentEditable) {
    return true
  }

  const tag = target.tagName
  if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
    return true
  }

  return Boolean(target.closest('[contenteditable="true"]'))
}

export function isDialogOpen(): boolean {
  return Boolean(
    document.querySelector('.p-dialog-mask:not(.p-component-overlay-leave)'),
  )
}

export interface ShortcutMatchOptions {
  key: string
  mod?: boolean
  shift?: boolean
  alt?: boolean
}

export function matchesShortcut(
  event: KeyboardEvent,
  { key, mod = false, shift = false, alt = false }: ShortcutMatchOptions,
): boolean {
  const eventKey = event.key.toLowerCase()
  const expectedKey = key.toLowerCase()

  if (key === '`') {
    if (event.code !== 'Backquote') {
      return false
    }
  } else if (eventKey !== expectedKey) {
    return false
  }
  if (event.shiftKey !== shift) {
    return false
  }
  if (event.altKey !== alt) {
    return false
  }
  if (mod && !isModKey(event)) {
    return false
  }

  return true
}

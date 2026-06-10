import { $prose } from '@milkdown/utils'
import { Plugin, PluginKey } from '@milkdown/prose/state'
import {
  getToolbarActiveCommands,
  isSameActiveCommands,
  type ToolbarCommand,
} from './toolbarActiveState'

const toolbarStateKey = new PluginKey('toolbarState')

let onToolbarStateChange: ((active: Set<ToolbarCommand>) => void) | null = null

export function registerToolbarStateCallback(
  callback: (active: Set<ToolbarCommand>) => void,
): () => void {
  onToolbarStateChange = callback
  return () => {
    onToolbarStateChange = null
  }
}

export const toolbarStatePlugin = $prose(() => {
  let lastActive = new Set<ToolbarCommand>()

  const notify = (active: Set<ToolbarCommand>) => {
    if (isSameActiveCommands(lastActive, active)) return
    lastActive = active
    onToolbarStateChange?.(active)
  }

  return new Plugin({
    key: toolbarStateKey,
    view(view) {
      notify(getToolbarActiveCommands(view.state))

      return {
        update(nextView, prevState) {
          if (
            !prevState
            || !nextView.state.selection.eq(prevState.selection)
            || !nextView.state.doc.eq(prevState.doc)
          ) {
            notify(getToolbarActiveCommands(nextView.state))
          }
        },
      }
    },
  })
})

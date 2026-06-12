import { $prose } from '@milkdown/utils'
import { Plugin, PluginKey } from '@milkdown/prose/state'

const wikiLinkInputKey = new PluginKey('wikiLinkInput')

export interface WikiLinkBracketTrigger {
  from: number
  to: number
}

let onWikiLinkBracket: ((trigger: WikiLinkBracketTrigger) => void) | null = null

export function registerWikiLinkBracketCallback(
  callback: (trigger: WikiLinkBracketTrigger) => void,
): () => void {
  onWikiLinkBracket = callback
  return () => {
    onWikiLinkBracket = null
  }
}

export const wikiLinkInputPlugin = $prose(() => {
  return new Plugin({
    key: wikiLinkInputKey,
    props: {
      handleTextInput(view, from, _to, text) {
        if (text !== '[') {
          return false
        }

        if (from >= 1 && view.state.doc.textBetween(from - 1, from) === '[') {
          const start = from - 1
          queueMicrotask(() => {
            onWikiLinkBracket?.({ from: start, to: start + 2 })
          })
        }

        return false
      },
    },
  })
})

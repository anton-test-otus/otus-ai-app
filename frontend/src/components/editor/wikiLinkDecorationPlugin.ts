import { $prose } from '@milkdown/utils'
import { Plugin, PluginKey } from '@milkdown/prose/state'
import type { Node } from '@milkdown/prose/model'
import { Decoration, DecorationSet } from '@milkdown/prose/view'
import { findWikiLinksInText } from '@/lib/wikiLinks'

const wikiLinkDecorationKey = new PluginKey('wikiLinkDecoration')

function buildDecorations(doc: Node): DecorationSet {
  const decorations: Decoration[] = []

  doc.descendants((node, pos) => {
    if (!node.isText || !node.text?.includes('[[')) return

    for (const match of findWikiLinksInText(node.text, pos)) {
      decorations.push(
        Decoration.inline(match.from, match.to, {
          class: 'wiki-link wiki-link-edit',
        }),
      )
    }
  })

  return decorations.length > 0
    ? DecorationSet.create(doc, decorations)
    : DecorationSet.empty
}

export const wikiLinkDecorationPlugin = $prose(() => {
  return new Plugin({
    key: wikiLinkDecorationKey,
    props: {
      decorations(state) {
        return buildDecorations(state.doc)
      },
    },
  })
})

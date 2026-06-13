import { $remark } from '@milkdown/utils'
import { visit } from 'unist-util-visit'
import type { Html, Root } from 'mdast'

/** Превращает raw HTML-узлы mdast в plain text — защита от XSS в preview/editor. */
function stripHtmlNodes() {
  return (tree: Root) => {
    visit(tree, 'html', (node: Html, index, parent) => {
      if (parent == null || index == null) return
      parent.children[index] = { type: 'text', value: node.value }
    })
  }
}

export const remarkStripHtmlPlugin = $remark('stripHtml', () => stripHtmlNodes)

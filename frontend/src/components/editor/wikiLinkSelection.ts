import { NodeSelection } from '@milkdown/prose/state'
import type { EditorState } from '@milkdown/prose/state'

export interface WikiLinkSelectionContext {
  pos: number
  noteId: string
  label: string
}

export function getWikiLinkSelectionContext(state: EditorState): WikiLinkSelectionContext | null {
  const { selection } = state

  if (selection instanceof NodeSelection && selection.node.type.name === 'wiki_link') {
    return {
      pos: selection.from,
      noteId: selection.node.attrs.noteId,
      label: selection.node.attrs.label || '',
    }
  }

  if (!selection.empty) {
    return null
  }

  const $pos = selection.$from
  const nodeAfter = $pos.nodeAfter
  if (nodeAfter?.type.name === 'wiki_link') {
    return {
      pos: $pos.pos,
      noteId: nodeAfter.attrs.noteId,
      label: nodeAfter.attrs.label || '',
    }
  }

  const nodeBefore = $pos.nodeBefore
  if (nodeBefore?.type.name === 'wiki_link') {
    return {
      pos: $pos.pos - nodeBefore.nodeSize,
      noteId: nodeBefore.attrs.noteId,
      label: nodeBefore.attrs.label || '',
    }
  }

  return null
}

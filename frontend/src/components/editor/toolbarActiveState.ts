import type { EditorState } from '@milkdown/prose/state'
import type { MarkType } from '@milkdown/prose/model'

export type ToolbarCommand =
  | 'bold'
  | 'italic'
  | 'code'
  | 'link'
  | 'bullet_list'
  | 'ordered_list'
  | 'blockquote'
  | 'heading'

function hasMark(state: EditorState, markType: MarkType | undefined): boolean {
  if (!markType) return false

  const { from, to, empty, $from } = state.selection
  if (empty) {
    return !!markType.isInSet(state.storedMarks || $from.marks())
  }

  return state.doc.rangeHasMark(from, to, markType)
}

function isInBlock(
  state: EditorState,
  nodeName: string,
  attrs?: Record<string, unknown>,
): boolean {
  const { $from } = state.selection

  for (let depth = $from.depth; depth > 0; depth--) {
    const node = $from.node(depth)
    if (node.type.name !== nodeName) continue

    if (attrs) {
      const matches = Object.entries(attrs).every(([key, value]) => node.attrs[key] === value)
      if (!matches) continue
    }

    return true
  }

  return false
}

export function getToolbarActiveCommands(state: EditorState): Set<ToolbarCommand> {
  const active = new Set<ToolbarCommand>()
  const { schema } = state

  if (hasMark(state, schema.marks.strong)) active.add('bold')
  if (hasMark(state, schema.marks.emphasis)) active.add('italic')
  if (hasMark(state, schema.marks.inlineCode)) active.add('code')
  if (hasMark(state, schema.marks.link)) active.add('link')

  if (isInBlock(state, 'bullet_list')) active.add('bullet_list')
  if (isInBlock(state, 'ordered_list')) active.add('ordered_list')
  if (isInBlock(state, 'blockquote')) active.add('blockquote')
  if (isInBlock(state, 'heading', { level: 2 })) active.add('heading')

  return active
}

export function isSameActiveCommands(a: Set<ToolbarCommand>, b: Set<ToolbarCommand>): boolean {
  if (a.size !== b.size) return false
  for (const command of a) {
    if (!b.has(command)) return false
  }
  return true
}

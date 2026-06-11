import type { Mark, MarkType, ResolvedPos } from '@milkdown/prose/model'
import type { EditorState } from '@milkdown/prose/state'

export interface LinkSelectionContext {
  from: number
  to: number
  href: string
  text: string
  isExisting: boolean
}

export function expandLinkMarkRange(
  $pos: ResolvedPos,
  linkType: MarkType,
): { from: number; to: number; mark: Mark } | null {
  const mark = linkType.isInSet($pos.marks())
  if (!mark) {
    return null
  }

  let from = $pos.pos
  let to = $pos.pos
  const doc = $pos.doc

  while (from > $pos.start() && linkType.isInSet(doc.resolve(from - 1).marks())) {
    from -= 1
  }

  while (to < $pos.end() && linkType.isInSet(doc.resolve(to).marks())) {
    to += 1
  }

  return { from, to, mark }
}

export function getLinkSelectionContext(state: EditorState): LinkSelectionContext {
  const linkType = state.schema.marks.link
  const { from, to, empty, $from } = state.selection

  if (!empty) {
    const text = state.doc.textBetween(from, to)
    const hasLink = linkType ? state.doc.rangeHasMark(from, to, linkType) : false
    let href = ''

    if (hasLink && linkType) {
      const mark = linkType.isInSet(state.doc.resolve(from).marks())
      href = mark?.attrs.href ?? ''
    }

    return {
      from,
      to,
      href,
      text,
      isExisting: hasLink,
    }
  }

  if (linkType) {
    const expanded = expandLinkMarkRange($from, linkType)
    if (expanded) {
      return {
        from: expanded.from,
        to: expanded.to,
        href: expanded.mark.attrs.href ?? '',
        text: state.doc.textBetween(expanded.from, expanded.to),
        isExisting: true,
      }
    }
  }

  return {
    from,
    to,
    href: '',
    text: '',
    isExisting: false,
  }
}

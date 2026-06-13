import type { Ctx } from '@milkdown/ctx'
import type { Node } from '@milkdown/prose/model'
import type { NodeViewConstructor } from '@milkdown/prose/view'
import { commandsCtx } from '@milkdown/core'
import { $command, $nodeSchema, $remark, $view } from '@milkdown/utils'
import { visit } from 'unist-util-visit'
import type { Parent, Root, Text } from 'mdast'
import { wikiLinksApi } from '@/api/wikilinks'
import {
  createWikiLinkPattern,
  formatWikiLink,
  isValidWikiLinkUuid,
  normalizeWikiLinkUuid,
} from '@/lib/wikiLinks'

interface WikiLinkMdastNode {
  type: 'wikiLink'
  noteId: string
  label: string
}

const resolvedTitleCache = new Map<string, string>()

interface ResolvedWikiLinkState {
  title: string
  exists: boolean
  id: string
}

export function clearWikiLinkTitleCache(): void {
  resolvedTitleCache.clear()
}

async function resolveWikiLink(noteId: string): Promise<ResolvedWikiLinkState> {
  const normalizedId = normalizeWikiLinkUuid(noteId)
  const cachedTitle = resolvedTitleCache.get(normalizedId)

  try {
    const response = await wikiLinksApi.resolveWikiLinks([normalizedId])
    const resolved = response[normalizedId]

    if (!resolved) {
      return {
        title: cachedTitle ?? 'Заметка',
        exists: false,
        id: normalizedId,
      }
    }

    resolvedTitleCache.set(normalizedId, resolved.title)
    return {
      title: resolved.title,
      exists: true,
      id: resolved.id,
    }
  } catch {
    return {
      title: cachedTitle ?? 'Заметка',
      exists: false,
      id: normalizedId,
    }
  }
}

function applyWikiLinkDomState(
  dom: HTMLElement,
  state: ResolvedWikiLinkState,
  displayText: string,
) {
  dom.textContent = displayText
  dom.dataset.wikiLink = 'true'
  dom.dataset.noteId = state.id
  dom.dataset.linkType = state.exists ? 'exists' : 'missing'
  dom.classList.remove('wiki-link-exists', 'wiki-link-missing')
  dom.classList.add(state.exists ? 'wiki-link-exists' : 'wiki-link-missing')
}

export const wikiLinkSchema = $nodeSchema('wiki_link', () => ({
  inline: true,
  group: 'inline',
  atom: true,
  selectable: true,
  attrs: {
    noteId: { default: '', validate: 'string' },
    label: { default: '', validate: 'string' },
  },
  parseDOM: [
    {
      tag: 'span[data-wiki-link]',
      getAttrs: (dom) => {
        if (!(dom instanceof HTMLElement)) {
          return false
        }

        return {
          noteId: dom.getAttribute('data-note-id') || '',
          label: dom.getAttribute('data-label') || '',
        }
      },
    },
  ],
  toDOM: (node) => {
    const display = node.attrs.label || 'Заметка'
    return [
      'span',
      {
        'data-wiki-link': 'true',
        'data-note-id': node.attrs.noteId,
        'data-label': node.attrs.label,
        class: 'wiki-link wiki-link-edit',
      },
      display,
    ]
  },
  parseMarkdown: {
    match: ({ type }) => type === 'wikiLink',
    runner: (state, node, type) => {
      const wikiNode = node as WikiLinkMdastNode
      state.addNode(type, {
        noteId: wikiNode.noteId,
        label: wikiNode.label || '',
      })
    },
  },
  toMarkdown: {
    match: (node) => node.type.name === 'wiki_link',
    runner: (state, node) => {
      const { noteId, label } = node.attrs
      state.addNode('wikiLink', undefined, undefined, {
        noteId: normalizeWikiLinkUuid(noteId),
        label: label?.trim() || '',
      })
    },
  },
}))

function wikiLinkToMarkdownExtension() {
  return {
    handlers: {
      wikiLink: (node: WikiLinkMdastNode) =>
        formatWikiLink(node.noteId, node.label || null),
    },
  }
}

/** Регистрирует mdast-handler, чтобы при сохранении выводить [[uuid|alias]] без экранирования */
function remarkWikiLinkStringify(this: { data: () => Record<string, unknown> }) {
  const data = this.data()
  const extensions = (data.toMarkdownExtensions as unknown[]) ?? []
  if (!data.toMarkdownExtensions) {
    data.toMarkdownExtensions = extensions
  }
  extensions.push(wikiLinkToMarkdownExtension())
}

export const remarkWikiLinkStringifyPlugin = $remark(
  'wikiLinkStringify',
  () => remarkWikiLinkStringify,
)

export const remarkWikiLinkPlugin = $remark('wikiLink', () => () => (tree: Root) => {
  visit(tree, 'text', (node: Text, index, parent: Parent | undefined) => {
    if (parent == null || index == null || (!node.value.includes('[[') && !node.value.includes('\\[\\['))) {
      return
    }

    const pattern = createWikiLinkPattern()
    const children: Array<Text | WikiLinkMdastNode> = []
    const value = node.value
    let lastIndex = 0
    let match: RegExpExecArray | null

    while ((match = pattern.exec(value)) !== null) {
      const noteId = normalizeWikiLinkUuid(match[1])
      if (!isValidWikiLinkUuid(noteId)) {
        continue
      }

      if (match.index > lastIndex) {
        children.push({ type: 'text', value: value.slice(lastIndex, match.index) })
      }

      children.push({
        type: 'wikiLink',
        noteId,
        label: match[2]?.trim() || '',
      })
      lastIndex = match.index + match[0].length
    }

    if (children.length === 0) {
      return
    }

    if (lastIndex < value.length) {
      children.push({ type: 'text', value: value.slice(lastIndex) })
    }

    parent.children.splice(index, 1, ...children)
  })
})

export interface InsertWikiLinkPayload {
  noteId: string
  label: string
  from?: number
  to?: number
}

export interface UpdateWikiLinkPayload {
  pos: number
  label: string
}

export interface WikiLinkEditRequest {
  pos: number
  noteId: string
  label: string
}

let onWikiLinkEditRequest: ((request: WikiLinkEditRequest) => void) | null = null

export function registerWikiLinkEditCallback(
  callback: (request: WikiLinkEditRequest) => void,
): () => void {
  onWikiLinkEditRequest = callback
  return () => {
    onWikiLinkEditRequest = null
  }
}

export async function resolveWikiLinkTitle(noteId: string): Promise<string> {
  const state = await resolveWikiLink(noteId)
  return state.title
}

export const updateWikiLinkCommand = $command(
  'UpdateWikiLink',
  (ctx) => (payload: UpdateWikiLinkPayload) => (state, dispatch) => {
    const node = state.doc.nodeAt(payload.pos)
    if (!node || node.type.name !== 'wiki_link') {
      return false
    }

    if (!dispatch) {
      return true
    }

    dispatch(
      state.tr
        .setNodeMarkup(payload.pos, undefined, {
          ...node.attrs,
          label: payload.label.trim(),
        })
        .scrollIntoView(),
    )
    return true
  },
)

export const insertWikiLinkCommand = $command(
  'InsertWikiLink',
  (ctx) => (payload: InsertWikiLinkPayload) => (state, dispatch) => {
    if (!dispatch) {
      return true
    }

    const node = wikiLinkSchema.type(ctx).create({
      noteId: normalizeWikiLinkUuid(payload.noteId),
      label: payload.label.trim(),
    })
    if (!node) {
      return true
    }

    const from = payload.from ?? state.selection.from
    const to = payload.to ?? state.selection.to

    dispatch(state.tr.replaceWith(from, to, node).scrollIntoView())
    return true
  },
)

export const wikiLinkNodeView = $view(
  wikiLinkSchema.node,
  (): NodeViewConstructor => (initialNode, view, getPos) => {
    const dom = document.createElement('span')
    dom.className = 'wiki-link wiki-link-edit'
    dom.setAttribute('contenteditable', 'false')
    if (view.editable) {
      dom.title = 'Нажмите, чтобы изменить текст ссылки'
    }

    let currentRequestId = 0
    let currentNode = initialNode

    const handleClick = (event: MouseEvent) => {
      if (!view.editable) {
        return
      }

      event.preventDefault()
      event.stopPropagation()

      const pos = getPos()
      if (pos == null) {
        return
      }

      onWikiLinkEditRequest?.({
        pos,
        noteId: currentNode.attrs.noteId,
        label: currentNode.attrs.label || '',
      })
    }

    dom.addEventListener('click', handleClick)

    const render = (node: Node) => {
      currentNode = node
      const requestId = ++currentRequestId
      const label = node.attrs.label?.trim()
      const noteId = normalizeWikiLinkUuid(node.attrs.noteId)

      if (label) {
        applyWikiLinkDomState(dom, { title: label, exists: true, id: noteId }, label)
      } else {
        dom.textContent = '…'
        dom.dataset.wikiLink = 'true'
        dom.dataset.noteId = noteId
        dom.classList.remove('wiki-link-exists', 'wiki-link-missing')
      }

      void resolveWikiLink(noteId).then((state) => {
        if (requestId !== currentRequestId) {
          return
        }

        const displayText = label || state.title
        applyWikiLinkDomState(dom, state, displayText)
      })
    }

    render(initialNode)

    return {
      dom,
      update(updatedNode) {
        if (updatedNode.type.name !== 'wiki_link') {
          return false
        }

        render(updatedNode)
        return true
      },
      destroy() {
        dom.removeEventListener('click', handleClick)
      },
    }
  },
)

export function updateWikiLinkInEditor(
  ctx: Ctx,
  payload: UpdateWikiLinkPayload,
): boolean {
  const commands = ctx.get(commandsCtx)
  return commands.call(updateWikiLinkCommand.key, payload)
}

export function insertWikiLinkIntoEditor(
  ctx: Ctx,
  payload: InsertWikiLinkPayload,
): boolean {
  const commands = ctx.get(commandsCtx)
  return commands.call(insertWikiLinkCommand.key, payload)
}

export const wikiLinkPreviewPlugins = [
  wikiLinkSchema,
  remarkWikiLinkPlugin,
  remarkWikiLinkStringifyPlugin,
  wikiLinkNodeView,
]

export const wikiLinkPlugins = [
  ...wikiLinkPreviewPlugins,
  insertWikiLinkCommand,
  updateWikiLinkCommand,
]

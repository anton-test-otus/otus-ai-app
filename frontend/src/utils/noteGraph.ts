import type { NoteGraphEdge, NoteGraphNode, NoteGraphResponse } from '@/api/wikilinks'
import type { Theme } from '@/types'

const MAX_NODE_LABEL_LENGTH = 28
const EDGE_SPRING_LENGTH = 160
const MUTUAL_EDGE_ROUNDNESS = 0.5

export const NOTE_GRAPH_DEFAULT_DEPTH = 1
export const NOTE_GRAPH_MAX_DEPTH = 3

export function hasNoteLinks(incoming: number, outgoing: number): boolean {
  return incoming > 0 || outgoing > 0
}

function truncateLabel(value: string, maxLength: number): string {
  const trimmed = value.trim()
  if (trimmed.length <= maxLength) {
    return trimmed || 'Без названия'
  }

  return `${trimmed.slice(0, maxLength - 1)}…`
}

function buildEdgeTooltip(
  edge: NoteGraphEdge,
  nodesById: Map<string, NoteGraphNode>,
): string {
  const target = nodesById.get(edge.target)
  const targetTitle = target?.title?.trim() || 'Без названия'
  const resolvedAliases = edge.aliases.map((alias) => alias?.trim() || targetTitle)

  return resolvedAliases.join('\n')
}

function getUndirectedPairKey(source: string, target: string): string {
  return source < target ? `${source}|${target}` : `${target}|${source}`
}

function buildMutualLinkPairKeys(edges: NoteGraphEdge[]): Set<string> {
  const directed = new Set(edges.map((edge) => `${edge.source}|${edge.target}`))
  const mutualPairs = new Set<string>()

  for (const edge of edges) {
    if (directed.has(`${edge.target}|${edge.source}`)) {
      mutualPairs.add(getUndirectedPairKey(edge.source, edge.target))
    }
  }

  return mutualPairs
}

interface EdgeVisualStyle {
  smooth: false | {
    enabled: true
    type: 'curvedCW'
    roundness: number
    forceDirection: 'none'
  }
}

function getEdgeVisualStyle(
  source: string,
  target: string,
  mutualPairs: Set<string>,
): EdgeVisualStyle {
  if (!mutualPairs.has(getUndirectedPairKey(source, target))) {
    return { smooth: false }
  }

  const isLowerToHigher = source < target

  return {
    smooth: {
      enabled: true,
      type: 'curvedCW',
      roundness: isLowerToHigher ? MUTUAL_EDGE_ROUNDNESS : -MUTUAL_EDGE_ROUNDNESS,
      forceDirection: 'none',
    },
  }
}

export function mergeNoteGraphData(
  base: NoteGraphResponse,
  incoming: NoteGraphResponse,
): NoteGraphResponse {
  const nodesMap = new Map(base.nodes.map((node) => [node.id, node]))
  for (const node of incoming.nodes) {
    nodesMap.set(node.id, node)
  }

  const edgesMap = new Map(base.edges.map((edge) => [edge.id, edge]))
  for (const edge of incoming.edges) {
    edgesMap.set(edge.id, edge)
  }

  return {
    nodes: [...nodesMap.values()],
    edges: [...edgesMap.values()],
    truncated: incoming.truncated,
    frontierNodeIds: incoming.frontierNodeIds,
  }
}

export interface GraphLegendItem {
  label: string
  background: string
  border: string
  size?: 'md' | 'lg'
}

export function getGraphLegendItems(theme: Theme): GraphLegendItem[] {
  const colors = getGraphColors(theme)

  return [
    {
      label: 'Текущая заметка',
      background: colors.focus.background,
      border: colors.focus.border,
      size: 'lg',
    },
    {
      label: 'Избранная',
      background: colors.favorite.background,
      border: colors.favorite.border,
    },
    {
      label: 'Связанная заметка',
      background: colors.default.background,
      border: colors.default.border,
    },
  ]
}

export function getGraphColors(theme: Theme) {
  const isDark = theme === 'dark'

  return {
    background: isDark ? '#0f172a' : '#ffffff',
    default: {
      background: isDark ? '#334155' : '#e2e8f0',
      border: isDark ? '#64748b' : '#94a3b8',
      highlight: {
        background: isDark ? '#475569' : '#cbd5e1',
        border: isDark ? '#94a3b8' : '#64748b',
      },
      hover: {
        background: isDark ? '#475569' : '#cbd5e1',
        border: isDark ? '#cbd5e1' : '#475569',
      },
      font: isDark ? '#e2e8f0' : '#1e293b',
    },
    focus: {
      background: isDark ? '#1d4ed8' : '#2563eb',
      border: isDark ? '#60a5fa' : '#1d4ed8',
      highlight: {
        background: isDark ? '#2563eb' : '#1d4ed8',
        border: isDark ? '#93c5fd' : '#60a5fa',
      },
      hover: {
        background: isDark ? '#1e3a8a' : '#1e40af',
        border: isDark ? '#bfdbfe' : '#93c5fd',
      },
      font: '#ffffff',
    },
    favorite: {
      background: isDark ? '#854d0e' : '#fbbf24',
      border: isDark ? '#fbbf24' : '#d97706',
      highlight: {
        background: isDark ? '#a16207' : '#f59e0b',
        border: isDark ? '#fcd34d' : '#b45309',
      },
      font: isDark ? '#fef3c7' : '#78350f',
    },
    edge: isDark ? '#64748b' : '#94a3b8',
    edgeHover: isDark ? '#cbd5e1' : '#475569',
  }
}

export function toVisNetworkData(
  data: NoteGraphResponse,
  focusNoteId: string,
) {
  const nodesById = new Map(data.nodes.map((node) => [node.id, node]))
  const mutualPairs = buildMutualLinkPairKeys(data.edges)

  const nodes = data.nodes.map((node) => {
    const fullTitle = node.title?.trim() || 'Без названия'

    return {
      id: node.id,
      label: truncateLabel(fullTitle, MAX_NODE_LABEL_LENGTH),
      title: fullTitle,
      group: node.id === focusNoteId
        ? 'focus'
        : node.isFavorite
          ? 'favorite'
          : 'default',
    }
  })

  const edges = data.edges.map((edge) => {
    const { smooth } = getEdgeVisualStyle(edge.source, edge.target, mutualPairs)

    return {
      id: edge.id,
      from: edge.source,
      to: edge.target,
      title: buildEdgeTooltip(edge, nodesById),
      smooth,
      arrows: { to: { enabled: true, scaleFactor: 0.7 } },
    }
  })

  return { nodes, edges }
}

export function getNetworkOptions(theme: Theme) {
  const colors = getGraphColors(theme)

  return {
    autoResize: true,
    layout: {
      improvedLayout: true,
    },
    interaction: {
      dragNodes: true,
      dragView: true,
      zoomView: true,
      hover: true,
      tooltipDelay: 200,
    },
    physics: {
      enabled: true,
      solver: 'forceAtlas2Based',
      forceAtlas2Based: {
        gravitationalConstant: -50,
        centralGravity: 0.008,
        springLength: EDGE_SPRING_LENGTH,
        springConstant: 0.08,
        damping: 0.4,
      },
      stabilization: {
        enabled: true,
        iterations: 150,
      },
    },
    nodes: {
      shape: 'box',
      shapeProperties: {
        borderRadius: 8,
      },
      margin: {
        top: 10,
        right: 12,
        bottom: 10,
        left: 12,
      },
      borderWidth: 2,
      font: {
        size: 12,
        face: 'Inter, system-ui, sans-serif',
        color: colors.default.font,
      },
      widthConstraint: {
        minimum: 90,
        maximum: 200,
      },
    },
    edges: {
      smooth: false,
      color: {
        color: colors.edge,
        highlight: colors.edgeHover,
        hover: colors.edgeHover,
      },
      width: 1.5,
    },
    groups: {
      default: {
        font: {
          color: colors.default.font,
          size: 12,
        },
        color: {
          background: colors.default.background,
          border: colors.default.border,
          highlight: colors.default.highlight,
          hover: colors.default.hover,
        },
      },
      focus: {
        font: {
          color: colors.focus.font,
          size: 13,
          bold: true,
        },
        color: {
          background: colors.focus.background,
          border: colors.focus.border,
          highlight: colors.focus.highlight,
          hover: colors.focus.hover,
        },
        chosen: {
          label: (values, _id, _selected, hovering) => {
            if (hovering) {
              values.color = colors.focus.font
            }
          },
        },
      },
      favorite: {
        font: {
          color: colors.favorite.font,
          size: 12,
        },
        color: {
          background: colors.favorite.background,
          border: colors.favorite.border,
          highlight: colors.favorite.highlight,
        },
      },
    },
  } as const
}

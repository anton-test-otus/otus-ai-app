<template>
  <Dialog
    v-model:visible="visible"
    modal
    header="Связанные заметки"
    :style="MODAL_WIDTH.xl"
    :breakpoints="MODAL_FULLSCREEN_MOBILE_BREAKPOINTS"
    :dismissable-mask="true"
    :class="MODAL_FULLSCREEN_MOBILE_CLASS"
    class="note-links-graph-dialog"
    @show="handleDialogShow"
    @hide="handleHide"
  >
    <LoadingState
      v-if="isLoading"
      compact
      message="Загрузка графа"
    />

    <ErrorState
      v-else-if="error"
      compact
      :message="error"
      @retry="loadGraph(currentDepth)"
    />

    <EmptyState
      v-else-if="isEmpty"
      compact
      icon="pi pi-share-alt"
      title="Нет связей"
      description="У этой заметки пока нет wiki-ссылок с другими заметками."
    />

    <div v-else class="note-links-graph">
      <ul
        class="note-links-graph__legend"
        aria-label="Легенда графа"
      >
        <li
          v-for="item in legendItems"
          :key="item.label"
          class="note-links-graph__legend-item"
        >
          <span
            class="note-links-graph__legend-swatch"
            :class="{ 'note-links-graph__legend-swatch--lg': item.size === 'lg' }"
            :style="{
              backgroundColor: item.background,
              borderColor: item.border,
            }"
            aria-hidden="true"
          />
          <span class="text-muted">{{ item.label }}</span>
        </li>
      </ul>

      <div
        ref="containerRef"
        class="note-links-graph__canvas app-border rounded-lg"
        :style="{ backgroundColor: canvasBackground }"
        role="img"
        aria-label="Интерактивный граф wiki-связей"
      />

      <p class="note-links-graph__hint text-xs text-muted mt-3">
        Полное название заметки — при наведении на узел. Текст wiki-ссылки — при наведении на связь.
        Взаимные ссылки показаны разведёнными дугами.
      </p>

      <p
        v-if="graphData?.truncated"
        class="note-links-graph__hint text-xs text-muted mt-1"
      >
        Показана часть связей. Нажмите «+1 уровень», чтобы загрузить больше узлов.
      </p>
    </div>

    <template #footer>
      <div class="flex flex-wrap items-center justify-between gap-3 w-full">
        <span v-if="graphData && !isLoading" class="text-xs text-muted">
          Узлов: {{ graphData.nodes.length }}, связей: {{ graphData.edges.length }}
        </span>
        <div class="flex items-center gap-2 ml-auto">
          <Button
            v-if="canExpand"
            label="+1 уровень"
            icon="pi pi-plus"
            severity="secondary"
            outlined
            size="small"
            :loading="isExpanding"
            :disabled="isLoading || isExpanding"
            @click="expandGraph"
          />
          <Button
            label="Закрыть"
            severity="secondary"
            text
            @click="visible = false"
          />
        </div>
      </div>
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, shallowRef, computed, watch, nextTick, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import { Network } from 'vis-network'
import type { Options } from 'vis-network'
import { DataSet } from 'vis-data'
import 'vis-network/styles/vis-network.min.css'
import LoadingState from '@/components/common/LoadingState.vue'
import ErrorState from '@/components/common/ErrorState.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import { MODAL_FULLSCREEN_MOBILE_BREAKPOINTS, MODAL_FULLSCREEN_MOBILE_CLASS, MODAL_WIDTH } from '@/constants/modal'
import { wikiLinksApi, type NoteGraphResponse } from '@/api/wikilinks'
import { useTheme } from '@/composables/useTheme'
import { getApiErrorMessage } from '@/utils/apiError'
import {
  getGraphColors,
  getGraphLegendItems,
  getNetworkOptions,
  mergeNoteGraphData,
  NOTE_GRAPH_DEFAULT_DEPTH,
  NOTE_GRAPH_MAX_DEPTH,
  toVisNetworkData,
} from '@/utils/noteGraph'

const props = defineProps<{
  noteId: string
  focusNoteId: string
}>()

const visible = defineModel<boolean>('visible', { required: true })

const router = useRouter()
const { theme } = useTheme()

const containerRef = ref<HTMLElement | null>(null)
const network = shallowRef<Network | null>(null)

const isLoading = ref(false)
const isExpanding = ref(false)
const error = ref<string | null>(null)
const graphData = ref<NoteGraphResponse | null>(null)
const currentDepth = ref(NOTE_GRAPH_DEFAULT_DEPTH)

const canvasBackground = computed(() => getGraphColors(theme.value).background)

const legendItems = computed(() => getGraphLegendItems(theme.value))

const isEmpty = computed(() => {
  if (!graphData.value) {
    return false
  }

  return graphData.value.edges.length === 0
})

const canExpand = computed(() => {
  if (!graphData.value || currentDepth.value >= NOTE_GRAPH_MAX_DEPTH) {
    return false
  }

  return graphData.value.truncated || graphData.value.frontierNodeIds.length > 0
})

function destroyNetwork() {
  if (containerRef.value) {
    containerRef.value.style.cursor = 'default'
  }
  network.value?.destroy()
  network.value = null
}

async function waitForContainer(): Promise<HTMLElement | null> {
  await nextTick()
  await new Promise<void>((resolve) => {
    requestAnimationFrame(() => resolve())
  })

  return containerRef.value
}

async function mountGraph() {
  if (!graphData.value || isEmpty.value || error.value) {
    return
  }

  const container = await waitForContainer()
  if (!container) {
    return
  }

  await renderGraph(container)
  network.value?.fit({ animation: false })
}

function attachNetworkCursorHandlers(instance: Network, container: HTMLElement) {
  const setCursor = (cursor: string) => {
    container.style.cursor = cursor

    const visNetwork = container.querySelector<HTMLElement>('.vis-network')
    if (visNetwork) {
      visNetwork.style.cursor = cursor
    }

    const canvas = container.querySelector<HTMLElement>('canvas')
    if (canvas) {
      canvas.style.cursor = cursor
    }

    const internalContainer = (
      instance as Network & { canvas?: { body?: { container?: HTMLElement } } }
    ).canvas?.body?.container

    if (internalContainer) {
      internalContainer.style.cursor = cursor
    }
  }

  instance.on('hoverNode', () => setCursor('pointer'))
  instance.on('blurNode', () => setCursor('default'))
}

async function renderGraph(container: HTMLElement) {
  if (!graphData.value) {
    return
  }

  const { nodes, edges } = toVisNetworkData(graphData.value, props.focusNoteId)
  const options = getNetworkOptions(theme.value) as Options
  const data = {
    nodes: new DataSet(nodes),
    edges: new DataSet(edges),
  }

  if (network.value) {
    network.value.setData(data)
    network.value.setOptions(options)
    network.value.redraw()
    network.value.stabilize()
    return
  }

  network.value = new Network(container, data, options)
  attachNetworkCursorHandlers(network.value, container)
  network.value.on('click', (params) => {
    const nodeId = params.nodes[0]
    if (!nodeId) {
      return
    }

    visible.value = false
    router.push({
      name: 'note',
      params: { id: nodeId },
      query: { mode: 'preview' },
    })
  })
}

async function loadGraph(depth = NOTE_GRAPH_DEFAULT_DEPTH) {
  if (!props.noteId) {
    return
  }

  isLoading.value = true
  error.value = null

  try {
    graphData.value = await wikiLinksApi.getGraph(props.noteId, depth)
    currentDepth.value = depth
  } catch (err) {
    error.value = getApiErrorMessage(err, 'Не удалось загрузить граф связей')
    graphData.value = null
    destroyNetwork()
  } finally {
    isLoading.value = false
  }

  if (graphData.value && !error.value) {
    await mountGraph()
  }
}

async function expandGraph() {
  if (!canExpand.value || isExpanding.value || !graphData.value) {
    return
  }

  const nextDepth = currentDepth.value + 1
  isExpanding.value = true
  error.value = null

  try {
    const extra = await wikiLinksApi.getGraph(props.noteId, nextDepth)
    graphData.value = mergeNoteGraphData(graphData.value, extra)
    currentDepth.value = nextDepth
    await mountGraph()
  } catch (err) {
    error.value = getApiErrorMessage(err, 'Не удалось загрузить дополнительный уровень')
  } finally {
    isExpanding.value = false
  }
}

function handleDialogShow() {
  if (graphData.value && !isLoading.value && !isEmpty.value) {
    void mountGraph()
  }
}

function handleHide() {
  destroyNetwork()
  graphData.value = null
  error.value = null
  currentDepth.value = NOTE_GRAPH_DEFAULT_DEPTH
}

watch(visible, async (open) => {
  if (!open) {
    return
  }

  destroyNetwork()
  currentDepth.value = NOTE_GRAPH_DEFAULT_DEPTH
  await loadGraph(NOTE_GRAPH_DEFAULT_DEPTH)
})

watch(() => theme.value, async () => {
  if (!visible.value || !graphData.value) {
    return
  }

  await mountGraph()
})

watch(() => props.noteId, async () => {
  if (!visible.value) {
    return
  }

  destroyNetwork()
  await loadGraph(NOTE_GRAPH_DEFAULT_DEPTH)
})

onBeforeUnmount(() => {
  destroyNetwork()
})
</script>

<style scoped>
.note-links-graph__legend {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem 1.25rem;
  margin-bottom: 0.75rem;
  list-style: none;
  padding: 0;
}

.note-links-graph__legend-item {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
}

.note-links-graph__legend-swatch {
  width: 1.25rem;
  height: 0.75rem;
  border-radius: 0.25rem;
  border: 2px solid;
  flex-shrink: 0;
}

.note-links-graph__legend-swatch--lg {
  width: 1.5rem;
  height: 0.875rem;
}

.note-links-graph__canvas {
  position: relative;
  width: 100%;
  height: min(60vh, 32rem);
  min-height: 20rem;
}

@media (max-width: 767px) {
  .note-links-graph-dialog.modal-fullscreen-mobile .note-links-graph {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
  }

  .note-links-graph-dialog.modal-fullscreen-mobile .note-links-graph__canvas {
    flex: 1;
    height: auto;
    min-height: 12rem;
  }
}

.note-links-graph__canvas :deep(.vis-network),
.note-links-graph__canvas :deep(.vis-network canvas) {
  width: 100% !important;
  height: 100% !important;
}
</style>

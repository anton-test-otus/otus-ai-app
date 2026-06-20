<template>
  <section class="dashboard-stats stack-sections" aria-label="Статистика заметок">
    <LoadingState v-if="loading" compact />

    <ErrorState
      v-else-if="error"
      :message="error"
      @retry="loadStats"
    />

    <template v-else-if="stats">
      <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">
        <Card
          v-for="item in kpiItems"
          :key="item.key"
          class="dashboard-stats-kpi"
        >
          <template #content>
            <div class="flex items-start gap-3">
              <div
                class="dashboard-stats-kpi-icon"
                :class="item.iconClass"
                aria-hidden="true"
              >
                <i :class="['pi', item.icon]" />
              </div>
              <div class="min-w-0">
                <p class="text-sm text-muted truncate">
                  {{ item.label }}
                </p>
                <p class="text-2xl font-semibold text-surface-900 dark:text-white">
                  {{ item.value }}
                </p>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <div
        v-if="hasCharts"
        class="grid grid-cols-1 lg:grid-cols-2 gap-4"
      >
        <Card v-if="folderChartData">
          <template #title>
            Заметки по папкам
          </template>
          <template #content>
            <p class="text-sm text-muted mb-3">
              Нажмите на сегмент, чтобы отфильтровать заметки
            </p>
            <Chart
              type="doughnut"
              :data="folderChartData"
              :options="chartOptions"
              class="dashboard-stats-chart"
            />
          </template>
        </Card>

        <Card v-if="tagChartData">
          <template #title>
            Популярные теги
          </template>
          <template #content>
            <p class="text-sm text-muted mb-3">
              Нажмите на тег, чтобы отфильтровать заметки
            </p>
            <Chart
              type="bar"
              :data="tagChartData"
              :options="barChartOptions"
              class="dashboard-stats-chart"
            />
          </template>
        </Card>
      </div>
    </template>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import type { ChartEvent, ActiveElement } from 'chart.js'
import Card from 'primevue/card'
import Chart from 'primevue/chart'
import LoadingState from '@/components/common/LoadingState.vue'
import ErrorState from '@/components/common/ErrorState.vue'
import { statsApi, type DashboardStats, type FolderNoteCount, type TagNoteCount } from '@/api/stats'
import { useTheme } from '@/composables/useTheme'
import { useLayoutPanels } from '@/composables/useLayoutPanels'
import { useFoldersStore } from '@/stores/folders'
import { useTagsStore } from '@/stores/tags'
import { getApiErrorMessage } from '@/utils/apiError'

const CHART_COLORS = [
  '#3B82F6',
  '#10B981',
  '#F59E0B',
  '#EF4444',
  '#8B5CF6',
  '#06B6D4',
  '#EC4899',
  '#84CC16',
  '#F97316',
  '#6366F1',
  '#14B8A6',
  '#A855F7',
]

const stats = ref<DashboardStats | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const { theme } = useTheme()
const router = useRouter()
const foldersStore = useFoldersStore()
const tagsStore = useTagsStore()
const layoutPanels = useLayoutPanels()

const folderChartItems = computed((): FolderNoteCount[] => stats.value?.notesByFolder ?? [])
const tagChartItems = computed((): TagNoteCount[] => stats.value?.topTags ?? [])

const kpiItems = computed(() => {
  if (!stats.value) {
    return []
  }

  return [
    {
      key: 'notes',
      label: 'Заметки',
      value: stats.value.notesCount,
      icon: 'pi-book',
      iconClass: 'dashboard-stats-kpi-icon--notes',
    },
    {
      key: 'folders',
      label: 'Папки',
      value: stats.value.foldersCount,
      icon: 'pi-folder',
      iconClass: 'dashboard-stats-kpi-icon--folders',
    },
    {
      key: 'tags',
      label: 'Теги',
      value: stats.value.tagsCount,
      icon: 'pi-tags',
      iconClass: 'dashboard-stats-kpi-icon--tags',
    },
    {
      key: 'links',
      label: 'Связи',
      value: stats.value.linksCount,
      icon: 'pi-share-alt',
      iconClass: 'dashboard-stats-kpi-icon--links',
    },
    {
      key: 'favorites',
      label: 'Избранное',
      value: stats.value.favoritesCount,
      icon: 'pi-star',
      iconClass: 'dashboard-stats-kpi-icon--favorites',
    },
    {
      key: 'trash',
      label: 'Корзина',
      value: stats.value.trashCount,
      icon: 'pi-trash',
      iconClass: 'dashboard-stats-kpi-icon--trash',
    },
  ]
})

const hasCharts = computed(
  () => Boolean(folderChartData.value || tagChartData.value),
)

const folderChartData = computed(() => {
  const items = folderChartItems.value
  if (items.length === 0) {
    return null
  }

  return {
    labels: items.map((item) => item.folderName),
    datasets: [
      {
        data: items.map((item) => item.count),
        backgroundColor: items.map((_, index) => CHART_COLORS[index % CHART_COLORS.length]),
        borderWidth: 0,
      },
    ],
  }
})

const tagChartData = computed(() => {
  const items = tagChartItems.value
  if (items.length === 0) {
    return null
  }

  return {
    labels: items.map((item) => item.tagName),
    datasets: [
      {
        label: 'Заметок',
        data: items.map((item) => item.count),
        backgroundColor: '#3B82F6',
        borderRadius: 6,
        maxBarThickness: 40,
      },
    ],
  }
})

function setChartCursor(event: ChartEvent, elements: ActiveElement[]) {
  const canvas = event.native?.target
  if (!(canvas instanceof HTMLCanvasElement)) {
    return
  }
  canvas.style.cursor = elements.length > 0 ? 'pointer' : 'default'
}

function openNotesWithFolderFilter(folder: FolderNoteCount) {
  tagsStore.clearTagSelection()

  if (folder.folderId) {
    foldersStore.selectFolder(folder.folderId)
  } else {
    foldersStore.clearFolderSelection()
  }

  layoutPanels?.closeNavigation?.()
  void router.push({ name: 'dashboard' })
}

function openNotesWithTagFilter(tag: TagNoteCount) {
  foldersStore.clearFolderSelection()
  tagsStore.clearTagSelection()
  tagsStore.toggleTagSelection(tag.tagId)

  layoutPanels?.closeNavigation?.()
  void router.push({ name: 'dashboard' })
}

function handleFolderChartClick(_event: ChartEvent, elements: ActiveElement[]) {
  const index = elements[0]?.index
  if (index === undefined) {
    return
  }

  const folder = folderChartItems.value[index]
  if (!folder) {
    return
  }

  openNotesWithFolderFilter(folder)
}

function handleTagChartClick(_event: ChartEvent, elements: ActiveElement[]) {
  const index = elements[0]?.index
  if (index === undefined) {
    return
  }

  const tag = tagChartItems.value[index]
  if (!tag) {
    return
  }

  openNotesWithTagFilter(tag)
}

const chartOptions = computed(() => ({
  maintainAspectRatio: false,
  onClick: handleFolderChartClick,
  onHover: setChartCursor,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        color: theme.value === 'dark' ? '#E5E7EB' : '#374151',
        boxWidth: 12,
      },
    },
    tooltip: {
      callbacks: {
        afterLabel: () => 'Нажмите, чтобы открыть заметки',
      },
    },
  },
}))

const barChartOptions = computed(() => ({
  maintainAspectRatio: false,
  indexAxis: 'y' as const,
  onClick: handleTagChartClick,
  onHover: setChartCursor,
  plugins: {
    legend: {
      display: false,
    },
    tooltip: {
      callbacks: {
        afterLabel: () => 'Нажмите, чтобы открыть заметки',
      },
    },
  },
  scales: {
    x: {
      ticks: {
        color: theme.value === 'dark' ? '#9CA3AF' : '#6B7280',
        precision: 0,
      },
      grid: {
        color: theme.value === 'dark' ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)',
      },
    },
    y: {
      ticks: {
        color: theme.value === 'dark' ? '#E5E7EB' : '#374151',
      },
      grid: {
        display: false,
      },
    },
  },
}))

async function loadStats() {
  loading.value = true
  error.value = null

  try {
    stats.value = await statsApi.getDashboardStats()
  } catch (err) {
    error.value = getApiErrorMessage(err, 'Не удалось загрузить статистику')
    stats.value = null
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadStats()
})
</script>

<style scoped>
.dashboard-stats-kpi :deep(.p-card-body) {
  padding: 1rem;
}

.dashboard-stats-kpi :deep(.p-card-content) {
  padding: 0;
}

.dashboard-stats-kpi-icon {
  @apply flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-lg;
}

.dashboard-stats-kpi-icon--notes {
  @apply bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300;
}

.dashboard-stats-kpi-icon--folders {
  @apply bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-300;
}

.dashboard-stats-kpi-icon--tags {
  @apply bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300;
}

.dashboard-stats-kpi-icon--links {
  @apply bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-300;
}

.dashboard-stats-kpi-icon--favorites {
  @apply bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300;
}

.dashboard-stats-kpi-icon--trash {
  @apply bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-300;
}

.dashboard-stats-chart {
  height: 18rem;
  cursor: pointer;
}
</style>

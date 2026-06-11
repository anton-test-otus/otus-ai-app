<template>
  <div class="page-container-narrow">
      <div class="page-header">
        <h1 class="page-title">Настройки</h1>
        <p class="page-subtitle mt-0 text-surface-600 dark:text-surface-400">
          Параметры редактирования и учётная запись
        </p>
      </div>

      <Card class="mb-6">
        <template #title>Редактирование</template>
        <template #content>
          <div class="stack-sections">
            <div class="flex flex-col gap-2">
              <label for="autosave-delay" class="font-medium text-surface-900 dark:text-white">
                Задержка автосохранения
              </label>
              <Dropdown
                id="autosave-delay"
                v-model="autosaveDelaySeconds"
                :options="autosaveOptions"
                option-label="label"
                option-value="value"
                placeholder="По умолчанию"
                show-clear
                append-to="body"
                class="w-full md:w-80"
              />
              <small class="text-muted">
                По умолчанию: {{ defaultAutosaveDelaySeconds }} сек
              </small>
            </div>

            <div class="flex flex-col gap-2">
              <label for="version-window" class="font-medium text-surface-900 dark:text-white">
                Окно версионирования
              </label>
              <Dropdown
                id="version-window"
                v-model="versionConsolidationWindowMinutes"
                :options="versionOptions"
                option-label="label"
                option-value="value"
                placeholder="По умолчанию"
                show-clear
                append-to="body"
                class="w-full md:w-80"
              />
              <small class="text-muted">
                Новая версия создаётся, если с последнего сохранения прошло больше выбранного интервала.
                По умолчанию: {{ defaultVersionConsolidationWindowMinutes }} мин.
                На каждую заметку хранится не более {{ MAX_NOTE_VERSIONS_PER_NOTE }} последних версий — при превышении
                лимита старые удаляются автоматически.
              </small>
            </div>

            <div class="flex gap-3">
              <Button
                label="Сохранить"
                icon="pi pi-check"
                :loading="saving"
                @click="saveSettings"
              />
            </div>
          </div>
        </template>
      </Card>

      <Card class="mb-6">
        <template #title>Внешний вид</template>
        <template #content>
          <div class="stack-sections">
            <div class="flex flex-col gap-2">
              <span class="font-medium text-surface-900 dark:text-white">Тема оформления</span>
              <SelectButton
                v-model="selectedTheme"
                :options="themeOptions"
                option-label="label"
                option-value="value"
                aria-labelledby="theme-label"
              />
              <small class="text-muted">
                Сохраняется локально на этом устройстве. При первом визите используется системная тема.
              </small>
            </div>
          </div>
        </template>
      </Card>

      <Card>
        <template #title>Аккаунт</template>
        <template #content>
          <div class="stack-sections">
            <div class="flex flex-col gap-2">
              <label class="font-medium text-surface-900 dark:text-white">Email</label>
              <InputText :model-value="authStore.user?.email" disabled class="w-full md:w-80" />
            </div>

            <div class="flex flex-col gap-2">
              <label class="font-medium text-surface-900 dark:text-white">Пароль</label>
              <p class="text-muted m-0">
                Смена пароля будет доступна в следующих обновлениях.
              </p>
            </div>
          </div>
        </template>
      </Card>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import SelectButton from 'primevue/selectbutton'
import { useAuthStore } from '@/stores/auth'
import { useTheme } from '@/composables/useTheme'
import { appConfig } from '@/config/app'
import {
  AUTOSAVE_DELAY_SECONDS_OPTIONS,
  VERSION_CONSOLIDATION_MINUTES_OPTIONS,
  MAX_NOTE_VERSIONS_PER_NOTE,
} from '@/constants/userSettings'
import { THEME_OPTIONS } from '@/constants/theme'
import type { Theme } from '@/types'

const authStore = useAuthStore()
const { theme, setTheme } = useTheme()
const toast = useToast()
const saving = ref(false)
const themeOptions = THEME_OPTIONS

const selectedTheme = computed({
  get: () => theme.value,
  set: (value: Theme) => setTheme(value),
})

const autosaveDelaySeconds = ref<number | null>(null)
const versionConsolidationWindowMinutes = ref<number | null>(null)

const defaultAutosaveDelaySeconds = computed(
  () => authStore.user?.defaults?.autosaveDelaySeconds ?? appConfig.autosaveDelaySeconds,
)

const defaultVersionConsolidationWindowMinutes = computed(
  () => authStore.user?.defaults?.versionConsolidationWindowMinutes
    ?? appConfig.versionConsolidationWindowMinutes,
)

const autosaveOptions = computed(() =>
  AUTOSAVE_DELAY_SECONDS_OPTIONS.map((value) => ({
    label: `${value} сек`,
    value,
  })),
)

const versionOptions = computed(() =>
  VERSION_CONSOLIDATION_MINUTES_OPTIONS.map((value) => ({
    label: `${value} мин`,
    value,
  })),
)

watch(
  () => authStore.user,
  (user) => {
    autosaveDelaySeconds.value = user?.settings?.autosaveDelaySeconds ?? null
    versionConsolidationWindowMinutes.value = user?.settings?.versionConsolidationWindowMinutes ?? null
  },
  { immediate: true },
)

async function saveSettings() {
  saving.value = true
  try {
    const success = await authStore.updateSettings({
      autosaveDelaySeconds: autosaveDelaySeconds.value,
      versionConsolidationWindowMinutes: versionConsolidationWindowMinutes.value,
    })

    if (success) {
      toast.add({
        severity: 'success',
        summary: 'Настройки сохранены',
        life: 3000,
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Ошибка',
        detail: authStore.error ?? 'Не удалось сохранить настройки',
        life: 5000,
      })
    }
  } finally {
    saving.value = false
  }
}
</script>

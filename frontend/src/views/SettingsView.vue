<template>
  <div class="page-container-narrow">
      <div class="page-header">
        <h1 class="page-title">Настройки</h1>
        <p class="page-subtitle mt-0 text-surface-600 dark:text-surface-400">
          {{ settingsSubtitle }}
        </p>
      </div>

      <Card class="mb-6">
        <template #title>Автосохранение и версии</template>
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

      <Card v-if="authStore.authUiEnabled">
        <template #title>Аккаунт</template>
        <template #content>
          <div class="stack-sections">
            <div class="flex flex-col gap-2">
              <label class="font-medium text-surface-900 dark:text-white">Email</label>
              <InputText :model-value="authStore.user?.email" disabled class="w-full md:w-80" />
            </div>

            <div class="flex flex-col gap-2">
              <div>
                <Button
                  :label="showPasswordForm ? 'Скрыть' : 'Сменить пароль'"
                  :icon="showPasswordForm ? 'pi pi-times' : 'pi pi-lock'"
                  severity="secondary"
                  text
                  size="small"
                  @click="togglePasswordForm"
                />
              </div>

              <form
                v-if="showPasswordForm"
                class="stack-sections mt-2"
                @submit.prevent="onChangePassword"
              >
                <div class="flex flex-col gap-2">
                  <label for="current-password" class="font-medium text-surface-900 dark:text-white">
                    Текущий пароль
                  </label>
                  <Password
                    id="current-password"
                    v-model="currentPassword"
                    placeholder="••••••••"
                    :class="{ 'p-invalid': errors.currentPassword }"
                    :feedback="false"
                    toggle-mask
                    class="w-full md:w-80"
                    input-class="w-full"
                    autocomplete="current-password"
                  />
                  <small v-if="errors.currentPassword" class="text-red-600">
                    {{ errors.currentPassword }}
                  </small>
                </div>

                <div class="flex flex-col gap-2">
                  <label for="new-password" class="font-medium text-surface-900 dark:text-white">
                    Новый пароль
                  </label>
                  <Password
                    id="new-password"
                    v-model="newPassword"
                    placeholder="••••••••"
                    :class="{ 'p-invalid': errors.newPassword }"
                    toggle-mask
                    class="w-full md:w-80"
                    input-class="w-full"
                    autocomplete="new-password"
                  />
                  <small v-if="errors.newPassword" class="text-red-600">
                    {{ errors.newPassword }}
                  </small>
                </div>

                <div class="flex flex-col gap-2">
                  <label for="confirm-new-password" class="font-medium text-surface-900 dark:text-white">
                    Подтверждение нового пароля
                  </label>
                  <Password
                    id="confirm-new-password"
                    v-model="confirmNewPassword"
                    placeholder="••••••••"
                    :class="{ 'p-invalid': errors.confirmNewPassword }"
                    :feedback="false"
                    toggle-mask
                    class="w-full md:w-80"
                    input-class="w-full"
                    autocomplete="new-password"
                  />
                  <small v-if="errors.confirmNewPassword" class="text-red-600">
                    {{ errors.confirmNewPassword }}
                  </small>
                </div>

                <div class="flex gap-3">
                  <Button
                    type="submit"
                    label="Сохранить пароль"
                    icon="pi pi-check"
                    :loading="changingPassword"
                  />
                </div>
              </form>
            </div>
          </div>
        </template>
      </Card>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { useAppToast } from '@/composables/useAppToast'
import Card from 'primevue/card'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import SelectButton from 'primevue/selectbutton'
import { z } from '@/lib/zod'
import { HttpError } from '@/api/client'
import { appConfig } from '@/config/app'
import { useAuthStore } from '@/stores/auth'
import { useTheme } from '@/composables/useTheme'
import {
  AUTOSAVE_DELAY_SECONDS_OPTIONS,
  VERSION_CONSOLIDATION_MINUTES_OPTIONS,
  MAX_NOTE_VERSIONS_PER_NOTE,
} from '@/constants/userSettings'
import { THEME_OPTIONS } from '@/constants/theme'
import type { Theme } from '@/types'

const authStore = useAuthStore()
const { theme, setTheme } = useTheme()
const { showSuccess, showError } = useAppToast()
const saving = ref(false)
const changingPassword = ref(false)
const showPasswordForm = ref(false)
const themeOptions = THEME_OPTIONS

const settingsSubtitle = computed(() =>
  authStore.authUiEnabled
    ? 'Автосохранение, оформление и безопасность аккаунта'
    : 'Автосохранение и оформление',
)

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

const passwordSchema = toTypedSchema(
  z.object({
    currentPassword: z.string({ required_error: 'Текущий пароль обязателен' })
      .min(1, 'Текущий пароль обязателен'),
    newPassword: z.string({ required_error: 'Новый пароль обязателен' })
      .min(6, 'Пароль должен содержать минимум 6 символов'),
    confirmNewPassword: z.string({ required_error: 'Подтверждение пароля обязательно' })
      .min(6, 'Подтверждение пароля должно содержать минимум 6 символов'),
  }).superRefine((data, ctx) => {
    if (data.newPassword !== data.confirmNewPassword) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: 'Пароли не совпадают',
        path: ['confirmNewPassword'],
      })
    }
    if (data.currentPassword && data.newPassword && data.currentPassword === data.newPassword) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: 'Новый пароль должен отличаться от текущего',
        path: ['newPassword'],
      })
    }
  }),
)

const { defineField, errors, handleSubmit, resetForm, setFieldError } = useForm({
  validationSchema: passwordSchema,
})

const [currentPassword] = defineField('currentPassword')
const [newPassword] = defineField('newPassword')
const [confirmNewPassword] = defineField('confirmNewPassword')

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
      showSuccess('Настройки сохранены')
    } else {
      showError(authStore.error, 'Не удалось сохранить настройки')
    }
  } finally {
    saving.value = false
  }
}

function togglePasswordForm() {
  showPasswordForm.value = !showPasswordForm.value
  if (!showPasswordForm.value) {
    resetForm()
  }
}

function applyServerFieldErrors(err: unknown) {
  if (!(err instanceof HttpError) || !err.response?.errors || typeof err.response.errors !== 'object') {
    return
  }

  const serverErrors = err.response.errors as Record<string, string>
  if (serverErrors.currentPassword) {
    setFieldError('currentPassword', serverErrors.currentPassword)
  }
  if (serverErrors.newPassword) {
    setFieldError('newPassword', serverErrors.newPassword)
  }
}

const onChangePassword = handleSubmit(async (values) => {
  changingPassword.value = true
  try {
    await authStore.changePassword({
      currentPassword: values.currentPassword,
      newPassword: values.newPassword,
    })

    showSuccess('Пароль успешно изменён', 'Пароль изменён')

    resetForm()
    showPasswordForm.value = false
  } catch (err: unknown) {
    applyServerFieldErrors(err)
    showError(authStore.error ?? err, 'Не удалось сменить пароль')
  } finally {
    changingPassword.value = false
  }
})
</script>

import { useToast } from 'primevue/usetoast'
import { getApiErrorMessage } from '@/utils/apiError'

const DEFAULT_LIFE = 3000

export function useAppToast() {
  const toast = useToast()

  function showSuccess(detail: string, summary = 'Успешно') {
    toast.add({ severity: 'success', summary, detail, life: DEFAULT_LIFE })
  }

  function showError(error: unknown, fallback: string, summary = 'Ошибка') {
    toast.add({
      severity: 'error',
      summary,
      detail: getApiErrorMessage(error, fallback),
      life: DEFAULT_LIFE,
    })
  }

  function showInfo(detail: string, summary = 'Информация') {
    toast.add({ severity: 'info', summary, detail, life: DEFAULT_LIFE })
  }

  return { showSuccess, showError, showInfo }
}

import { HttpError } from '@/api/client'

const AUTH_ERROR_MESSAGES: Record<string, string> = {
  'Invalid credentials.': 'Неверный email или пароль',
}

export function getApiErrorMessage(err: unknown, fallback: string): string {
  if (err instanceof HttpError) {
    const data = err.response
    if (typeof data?.detail === 'string') {
      return data.detail
    }
    if (typeof data?.message === 'string') {
      return AUTH_ERROR_MESSAGES[data.message] ?? data.message
    }
    if (typeof data?.error === 'string') {
      return data.error
    }
  }

  if (err instanceof Error && err.message && !err.message.startsWith('HTTP ')) {
    return err.message
  }

  return fallback
}

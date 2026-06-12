import { pluralizeMinutes, pluralizeRu, pluralizeSeconds } from './pluralize'

/** Короткий относительный текст для индикатора автосохранения (секунды / минуты). */
export function formatSavedAgo(savedAt: Date, now: Date = new Date()): string {
  const diffSec = Math.max(0, Math.floor((now.getTime() - savedAt.getTime()) / 1000))

  if (diffSec < 5) return 'Сохранено только что'

  if (diffSec < 60) {
    return `Сохранено ${diffSec} ${pluralizeSeconds(diffSec)} назад`
  }

  const diffMin = Math.floor(diffSec / 60)
  if (diffMin < 60) {
    return `Сохранено ${diffMin} ${pluralizeMinutes(diffMin)} назад`
  }

  const diffHours = Math.floor(diffMin / 60)
  if (diffHours < 24) {
    return `Сохранено ${diffHours} ${pluralizeRu(diffHours, 'час', 'часа', 'часов')} назад`
  }

  return `Сохранено ${formatRelativeDate(savedAt.toISOString(), now).toLowerCase()}`
}

export function formatRelativeDate(dateString: string, now: Date = new Date()): string {
  const date = new Date(dateString)
  const diffMs = now.getTime() - date.getTime()
  const minutes = Math.floor(diffMs / (1000 * 60))
  const hours = Math.floor(diffMs / (1000 * 60 * 60))
  const days = Math.floor(diffMs / (1000 * 60 * 60 * 24))

  if (minutes < 1) return 'Только что'
  if (minutes < 60) return `${minutes} ${pluralizeMinutes(minutes)} назад`
  if (hours < 24) return `${hours} ${pluralizeRu(hours, 'час', 'часа', 'часов')} назад`
  if (days === 1) return 'Вчера'
  if (days < 7) return `${days} ${pluralizeRu(days, 'день', 'дня', 'дней')} назад`

  return date.toLocaleString('ru-RU', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

export function formatDateTime(dateString: string): string {
  return new Date(dateString).toLocaleString('ru-RU', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

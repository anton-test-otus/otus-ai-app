export interface FolderIconOption {
  value: string
  label: string
}

/** PrimeIcons names (without "pi-" prefix). Must match backend FolderIconChoices. */
export const FOLDER_ICON_OPTIONS: FolderIconOption[] = [
  { value: 'folder', label: 'Папка' },
  { value: 'bookmark', label: 'Закладка' },
  { value: 'star', label: 'Звезда' },
  { value: 'heart', label: 'Сердце' },
  { value: 'book', label: 'Книга' },
  { value: 'briefcase', label: 'Портфель' },
  { value: 'home', label: 'Дом' },
  { value: 'globe', label: 'Глобус' },
  { value: 'tag', label: 'Тег' },
  { value: 'calendar', label: 'Календарь' },
  { value: 'clock', label: 'Часы' },
  { value: 'lightbulb', label: 'Идея' },
  { value: 'code', label: 'Код' },
  { value: 'chart-bar', label: 'График' },
  { value: 'users', label: 'Группа' },
  { value: 'user', label: 'Пользователь' },
  { value: 'shield', label: 'Щит' },
  { value: 'lock', label: 'Замок' },
  { value: 'inbox', label: 'Входящие' },
  { value: 'file', label: 'Файл' },
  { value: 'image', label: 'Изображение' },
  { value: 'music', label: 'Музыка' },
  { value: 'video', label: 'Видео' },
  { value: 'map', label: 'Карта' },
  { value: 'flag', label: 'Флаг' },
  { value: 'bolt', label: 'Молния' },
  { value: 'sun', label: 'Солнце' },
  { value: 'moon', label: 'Луна' },
  { value: 'cloud', label: 'Облако' },
  { value: 'database', label: 'База данных' },
  { value: 'box', label: 'Коробка' },
  { value: 'shopping-cart', label: 'Корзина' },
  { value: 'graduation-cap', label: 'Обучение' },
  { value: 'wrench', label: 'Инструменты' },
]

export const FOLDER_ICON_VALUES = new Set(FOLDER_ICON_OPTIONS.map((option) => option.value))

export const DEFAULT_FOLDER_ICON = 'folder'

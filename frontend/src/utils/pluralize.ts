/**
 * Именительный падеж: 1, 21, 31… → one; 2–4, 22–24… → few; 5–20, 25–30… → many
 */
export function pluralizeRu(count: number, one: string, few: string, many: string): string {
  const mod10 = count % 10
  const mod100 = count % 100

  if (mod10 === 1 && mod100 !== 11) return one
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return few
  return many
}

export interface QuantifiedUnitForms {
  /** 1 минуту, 1 секунду */
  oneAccusative: string
  /** 21 минута, 31 секунда */
  one: string
  few: string
  many: string
}

/**
 * Склонение после числительного в конструкции «N … назад»:
 * 1 → винительный; 21, 31… → ед. ч.; 2–4 → few; иначе many.
 */
export function pluralizeRuQuantified(count: number, forms: QuantifiedUnitForms): string {
  if (count === 1) return forms.oneAccusative

  const mod10 = count % 10
  const mod100 = count % 100

  if (mod10 === 1 && mod100 !== 11) return forms.one
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return forms.few
  return forms.many
}

const MINUTE_FORMS: QuantifiedUnitForms = {
  oneAccusative: 'минуту',
  one: 'минута',
  few: 'минуты',
  many: 'минут',
}

const SECOND_FORMS: QuantifiedUnitForms = {
  oneAccusative: 'секунду',
  one: 'секунда',
  few: 'секунды',
  many: 'секунд',
}

export function pluralizeMinutes(count: number): string {
  return pluralizeRuQuantified(count, MINUTE_FORMS)
}

export function pluralizeSeconds(count: number): string {
  return pluralizeRuQuantified(count, SECOND_FORMS)
}

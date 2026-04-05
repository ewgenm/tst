// ============================================================
// useDateFormatter composable (CRITICAL FIX #3, #13)
// ТЗ №2 v1.1 — раздел 4.1
// ============================================================

import { formatDistanceToNow, parseISO } from 'date-fns'
import { toZonedTime, format as formatTz } from 'date-fns-tz'
import { computed } from 'vue'
import { ru, enUS } from 'date-fns/locale'
import { useAuthStore } from '@/stores/auth'

export function useDateFormatter() {
  const authStore = useAuthStore()
  const userTimezone = computed(() => authStore.userTimezone || 'UTC')
  const userLocale = computed(() => authStore.userLocale || 'ru')

  const localeMap: Record<string, any> = {
    ru,
    en: enUS,
    enUS: enUS,
  }

  const locale = computed(() => localeMap[userLocale.value] || ru)

  // Форматирование даты (UTC → пользовательский пояс)
  function formatDueDate(utcDateString: string | null, formatStr: string = 'dd MMM yyyy, HH:mm'): string {
    if (!utcDateString) return 'Без срока'

    const date = parseISO(utcDateString)
    const zonedDate = toZonedTime(date, userTimezone.value)
    return formatTz(zonedDate, formatStr, {
      timeZone: userTimezone.value,
      locale: locale.value
    })
  }

  // Относительное время
  function formatDueRelative(utcDateString: string | null): string {
    if (!utcDateString) return ''

    const date = parseISO(utcDateString)
    const zonedDate = toZonedTime(date, userTimezone.value)
    return formatDistanceToNow(zonedDate, {
      addSuffix: true,
      locale: locale.value
    })
  }

  // Проверка на просрочку
  function isOverdue(utcDateString: string | null): boolean {
    if (!utcDateString) return false
    const date = parseISO(utcDateString)
    return date < new Date()
  }

  // Конвертация локальной даты в UTC
  function localToUTC(localDate: Date): string {
    return localDate.toISOString()
  }

  // Короткий формат
  function formatShort(utcDateString: string | null): string {
    if (!utcDateString) return '—'

    const date = parseISO(utcDateString)
    const zonedDate = toZonedTime(date, userTimezone.value)
    const now = new Date()
    const isToday = zonedDate.toDateString() === now.toDateString()

    if (isToday) {
      return formatTz(zonedDate, 'HH:mm', { timeZone: userTimezone.value, locale: locale.value })
    }

    return formatTz(zonedDate, 'dd MMM', { timeZone: userTimezone.value, locale: locale.value })
  }

  return {
    formatDueDate,
    formatDueRelative,
    formatShort,
    isOverdue,
    localToUTC,
    userTimezone,
    userLocale,
  }
}

// ============================================================
// Habits Store (CRITICAL FIX #4) — ТЗ №2 v1.1 раздел 3.4
// ============================================================

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Habit, HabitCompletion } from '@/types'
import { apiClient } from '@/api/client'
import { endpoints } from '@/api/endpoints'
import { useUIStore } from '@/stores/ui'

export const useHabitsStore = defineStore('habits', () => {
  const habits = ref<Habit[]>([])
  const completions = ref<Record<number, HabitCompletion[]>>({})
  const isLoading = ref(false)
  const isLogging = ref(false)

  const activeHabits = computed(() => habits.value.filter(h => h.current_streak > 0 || h.completions_count))

  async function fetchHabits() {
    isLoading.value = true
    try {
      const response = await apiClient.get(endpoints.habits)
      habits.value = response.data.data
    } finally { isLoading.value = false }
  }

  async function createHabit(payload: Partial<Habit>) {
    const response = await apiClient.post(endpoints.habits, payload)
    habits.value.push(response.data.data)
    useUIStore().showToast('Привычка создана', 'success')
    return response.data.data
  }

  async function updateHabit(id: number, payload: Partial<Habit>) {
    const response = await apiClient.put(endpoints.habit(id), payload)
    const index = habits.value.findIndex(h => h.id === id)
    if (index !== -1) habits.value[index] = response.data.data
    useUIStore().showToast('Привычка обновлена', 'success')
    return response.data.data
  }

  async function deleteHabit(id: number) {
    await apiClient.delete(endpoints.habit(id))
    habits.value = habits.value.filter(h => h.id !== id)
    useUIStore().showToast('Привычка удалена', 'success')
  }

  async function logCompletion(habitId: number, date?: string) {
    isLogging.value = true
    try {
      const response = await apiClient.post(endpoints.habitLog(habitId), { date })
      const index = habits.value.findIndex(h => h.id === habitId)
      if (index !== -1) habits.value[index] = response.data.data
      useUIStore().showToast('Привычка выполнена! 🔥', 'success')
      return response.data.data
    } finally { isLogging.value = false }
  }

  async function fetchHabitStats(habitId: number) {
    const response = await apiClient.get(endpoints.habitStats(habitId))
    completions.value[habitId] = response.data.data.completions || []
    return response.data.data
  }

  function isCompletedOnDate(habitId: number, date: string): boolean {
    return (completions.value[habitId] || []).some(c => c.completed_date === date)
  }

  return {
    habits, completions, isLoading, isLogging, activeHabits,
    fetchHabits, createHabit, updateHabit, deleteHabit,
    logCompletion, fetchHabitStats, isCompletedOnDate,
  }
})

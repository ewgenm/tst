// ============================================================
// Auth Store (ТЗ №2 v1.1 — раздел 3.1)
// ============================================================

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types'
import { apiClient } from '@/api/client'
import { endpoints } from '@/api/endpoints'
import { useUIStore } from '@/stores/ui'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => !!user.value)
  const isEmailVerified = computed(() => !!user.value?.email_verified_at)
  const userTimezone = computed(() => user.value?.timezone || 'UTC')
  const userLocale = computed(() => user.value?.locale || 'ru')

  // ============================================================
  // Получить текущего пользователя
  // ============================================================
  async function fetchMe() {
    try {
      const response = await apiClient.get(endpoints.auth.me)
      user.value = response.data.data
      return user.value
    } catch {
      user.value = null
      return null
    }
  }

  // ============================================================
  // Логин
  // ============================================================
  async function login(email: string, password: string) {
    isLoading.value = true
    error.value = null

    try {
      const response = await apiClient.post(endpoints.auth.login, { email, password })
      user.value = response.data.data.user

      // Применить тему из профиля
      const uiStore = useUIStore()
      if (user.value?.theme) {
        uiStore.setTheme(user.value.theme)
      }

      return { success: true }
    } catch (err: any) {
      error.value = err.response?.data?.error?.message || 'Ошибка входа'
      return {
        success: false,
        error: error.value
      }
    } finally {
      isLoading.value = false
    }
  }

  // ============================================================
  // Регистрация
  // ============================================================
  async function register(name: string, email: string, password: string) {
    isLoading.value = true
    error.value = null

    try {
      const response = await apiClient.post(endpoints.auth.register, { name, email, password })
      user.value = response.data.data.user
      return { success: true }
    } catch (err: any) {
      error.value = err.response?.data?.error?.message || 'Ошибка регистрации'
      return {
        success: false,
        error: error.value
      }
    } finally {
      isLoading.value = false
    }
  }

  // ============================================================
  // Логаут
  // ============================================================
  async function logout() {
    try {
      await apiClient.post(endpoints.auth.logout)
    } catch {
      // Игнорируем ошибки при логауте
    } finally {
      user.value = null
    }
  }

  // ============================================================
  // Обновить профиль
  // ============================================================
  async function updateProfile(payload: Partial<User>) {
    const response = await apiClient.put(endpoints.auth.me, payload)
    user.value = response.data.data
    return user.value
  }

  return {
    user,
    isLoading,
    error,
    isAuthenticated,
    isEmailVerified,
    userTimezone,
    userLocale,
    fetchMe,
    login,
    register,
    logout,
    updateProfile,
  }
})

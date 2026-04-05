// ============================================================
// API Client с интерцепторами (CSRF, error handling, retry)
// ТЗ №2 v1.1 — CRITICAL FIX #3, #6, #7, #11, #17
// ============================================================

import axios from 'axios'
import type { AxiosError, InternalAxiosRequestConfig, AxiosResponse } from 'axios'
import type { ApiError } from '@/types'

// Глобальный callback для toast уведомлений
let showToast: ((message: string, type: 'success' | 'error' | 'warning' | 'info') => void) | null = null

export function setToastCallback(callback: typeof showToast) {
  showToast = callback
}

export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  withCredentials: true, // Для Sanctum cookies
})

// ============================================================
// Request interceptor - CSRF token
// ============================================================
apiClient.interceptors.request.use(
  async (config: InternalAxiosRequestConfig) => {
    // CSRF токен для stateful запросов (Sanctum)
    if (['post', 'put', 'patch', 'delete'].includes(config.method?.toLowerCase() || '')) {
      try {
        await axios.get('/sanctum/csrf-cookie', {
          withCredentials: true,
          baseURL: window.location.origin,
        })
      } catch (error) {
        console.warn('Не удалось получить CSRF токен:', error)
      }
    }
    return config
  },
  (error) => Promise.reject(error)
)

// ============================================================
// Response interceptor - error handling с retry logic
// ============================================================
apiClient.interceptors.response.use(
  (response: AxiosResponse) => response,
  async (error: AxiosError<ApiError>) => {
    const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean }

    // ==========================================================
    // Rate limiting (429) — retry после задержки (CRITICAL FIX #17)
    // ==========================================================
    if (error.response?.status === 429 && !originalRequest._retry) {
      const retryAfter = error.response.headers['retry-after'] || '60'
      originalRequest._retry = true

      showToast?.(`Слишком много запросов. Повтор через ${retryAfter} сек.`, 'warning')

      await new Promise(resolve => setTimeout(resolve, Number(retryAfter) * 1000))
      return apiClient(originalRequest)
    }

    // ==========================================================
    // Unauthorized (401) — очищаем токен и редиректим на логин
    // ==========================================================
    if (error.response?.status === 401) {
      // Очищаем токен
      localStorage.removeItem('tasksync_auth_token')
      delete apiClient.defaults.headers.common['Authorization']

      const isAuthPage = window.location.pathname === '/login' ||
                         window.location.pathname === '/register'

      if (!isAuthPage) {
        showToast?.('Сессия истекла. Пожалуйста, войдите снова.', 'error')
        window.location.href = '/login'
      }
      return Promise.reject(error)
    }

    // ==========================================================
    // Forbidden (403)
    // ==========================================================
    if (error.response?.status === 403) {
      showToast?.('У вас нет доступа к этому ресурсу.', 'error')
      return Promise.reject(error)
    }

    // ==========================================================
    // Validation errors (422) — field-level (CRITICAL FIX #6)
    // ==========================================================
    if (error.response?.status === 422) {
      const data = error.response.data

      if (data?.error?.details) {
        window.dispatchEvent(new CustomEvent('validation-errors', {
          detail: data.error.details
        }))
      }

      showToast?.(data?.error?.message || 'Ошибка валидации.', 'error')
      return Promise.reject(error)
    }

    // ==========================================================
    // Not Found (404)
    // ==========================================================
    if (error.response?.status === 404) {
      const data = error.response.data
      showToast?.(data?.error?.message || 'Ресурс не найден.', 'warning')
      return Promise.reject(error)
    }

    // ==========================================================
    // Server errors (5xx)
    // ==========================================================
    if (error.response && error.response.status >= 500) {
      showToast?.('Внутренняя ошибка сервера. Попробуйте позже.', 'error')
      return Promise.reject(error)
    }

    // ==========================================================
    // Network errors
    // ==========================================================
    if (!error.response) {
      showToast?.('Ошибка сети. Проверьте подключение к интернету.', 'error')
      return Promise.reject(error)
    }

    return Promise.reject(error)
  }
)

export { axios }

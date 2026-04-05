// ============================================================
// UI Store (ТЗ №2 v1.1 — раздел 3.7)
// ============================================================

import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useUIStore = defineStore('ui', () => {
  const sidebarCollapsed = ref(false)
  const currentModal = ref<string | null>(null)
  const theme = ref<'light' | 'dark' | 'system'>('system')
  const toast = ref<{ message: string; type: 'success' | 'error' | 'warning' | 'info' } | null>(null)
  let toastTimeout: ReturnType<typeof setTimeout> | null = null

  // ============================================================
  // Sidebar
  // ============================================================
  function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value
  }

  function setSidebarCollapsed(value: boolean) {
    sidebarCollapsed.value = value
  }

  // ============================================================
  // Модальные окна
  // ============================================================
  function openModal(name: string) {
    currentModal.value = name
  }

  function closeModal() {
    currentModal.value = null
  }

  // ============================================================
  // Тема
  // ============================================================
  function setTheme(newTheme: 'light' | 'dark' | 'system') {
    theme.value = newTheme
    localStorage.setItem('theme', newTheme)
    applyTheme(newTheme)
  }

  function applyTheme(newTheme: 'light' | 'dark' | 'system') {
    const html = document.documentElement
    if (newTheme === 'system') {
      const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches
      html.classList.toggle('dark', systemDark)
    } else {
      html.classList.toggle('dark', newTheme === 'dark')
    }
  }

  function initTheme() {
    const savedTheme = localStorage.getItem('theme') as 'light' | 'dark' | 'system' | null
    if (savedTheme) {
      theme.value = savedTheme
      applyTheme(savedTheme)
    } else {
      applyTheme('system')
    }
  }

  // ============================================================
  // Toast уведомления
  // ============================================================
  function showToast(message: string, type: 'success' | 'error' | 'warning' | 'info' = 'info') {
    if (toastTimeout) {
      clearTimeout(toastTimeout)
    }

    toast.value = { message, type }

    const duration = type === 'error' ? 5000 : 3000
    toastTimeout = setTimeout(() => {
      if (toast.value?.message === message) {
        toast.value = null
      }
    }, duration)
  }

  function hideToast() {
    toast.value = null
    if (toastTimeout) {
      clearTimeout(toastTimeout)
    }
  }

  // Слушаем системные изменения темы
  watch(theme, (newTheme) => {
    if (newTheme === 'system') {
      const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
      const handler = () => applyTheme('system')
      mediaQuery.addEventListener('change', handler)
      return () => mediaQuery.removeEventListener('change', handler)
    }
  })

  // Инициализация
  initTheme()

  return {
    sidebarCollapsed,
    currentModal,
    theme,
    toast,
    toggleSidebar,
    setSidebarCollapsed,
    openModal,
    closeModal,
    setTheme,
    showToast,
    hideToast,
  }
})

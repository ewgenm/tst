// ============================================================
// Notifications Store (CRITICAL FIX #4) — ТЗ №2 v1.1 раздел 3.5
// ============================================================

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Notification, WsEventNotificationNew } from '@/types'
import { apiClient } from '@/api/client'
import { endpoints } from '@/api/endpoints'
import { useWebSocket } from '@/composables/useWebSocket'
import { useUIStore } from '@/stores/ui'

export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref<Notification[]>([])
  const unreadCount = ref(0)
  const isLoading = ref(false)

  const unreadNotifications = computed(() => notifications.value.filter(n => !n.is_read))

  async function fetchNotifications() {
    isLoading.value = true
    try {
      const response = await apiClient.get(endpoints.notifications)
      notifications.value = response.data.data
      unreadCount.value = response.data.data.filter((n: Notification) => !n.is_read).length
    } finally {
      isLoading.value = false
    }
  }

  async function markAsRead(id: number) {
    await apiClient.put(endpoints.notificationRead(id))
    const notification = notifications.value.find(n => n.id === id)
    if (notification) { notification.is_read = true; unreadCount.value = Math.max(0, unreadCount.value - 1) }
  }

  async function markAllAsRead() {
    await apiClient.put(endpoints.notificationsReadAll)
    notifications.value.forEach(n => n.is_read = true)
    unreadCount.value = 0
  }

  async function fetchUnreadCount() {
    try {
      const response = await apiClient.get(endpoints.notificationsUnreadCount)
      unreadCount.value = response.data.data.count
    } catch { /* ignore */ }
  }

  function setupRealtime() {
    const { subscribeToUserChannel } = useWebSocket()
    subscribeToUserChannel('notification.new', (payload: WsEventNotificationNew) => {
      notifications.value.unshift(payload.notification)
      unreadCount.value++
      useUIStore().showToast(payload.notification.title, 'info')
    })
  }

  return {
    notifications, unreadCount, unreadNotifications, isLoading,
    fetchNotifications, markAsRead, markAllAsRead, fetchUnreadCount, setupRealtime,
  }
})

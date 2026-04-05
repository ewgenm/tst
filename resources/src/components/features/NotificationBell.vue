<!-- ============================================================
NotificationBell компонент
============================================================ -->

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useNotificationsStore } from '@/stores/notifications'
import { useDateFormatter } from '@/composables/useDateFormatter'

const notificationsStore = useNotificationsStore()
const { formatDueRelative } = useDateFormatter()
const isOpen = ref(false)

onMounted(async () => { await notificationsStore.fetchUnreadCount() })

function toggleDropdown() {
  isOpen.value = !isOpen.value
  if (isOpen.value && notificationsStore.notifications.length === 0) notificationsStore.fetchNotifications()
}

async function markAsRead(id: number) { await notificationsStore.markAsRead(id) }
async function markAllAsRead() { await notificationsStore.markAllAsRead() }

function handleClickOutside(event: MouseEvent) {
  const target = event.target as HTMLElement
  if (!target.closest('.notification-dropdown')) isOpen.value = false
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<template>
  <div class="relative notification-dropdown">
    <button class="relative p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" @click.stop="toggleDropdown">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
      </svg>
      <span v-if="notificationsStore.unreadCount > 0" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-semibold">
        {{ notificationsStore.unreadCount > 9 ? '9+' : notificationsStore.unreadCount }}
      </span>
    </button>

    <div v-if="isOpen" class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50">
      <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
        <h3 class="font-semibold text-sm">Уведомления</h3>
        <button v-if="notificationsStore.unreadCount > 0" class="text-xs text-primary-600 hover:underline" @click="markAllAsRead">Прочитать все</button>
      </div>
      <div class="max-h-96 overflow-y-auto">
        <div v-if="notificationsStore.isLoading" class="px-4 py-8 text-center">
          <div class="w-6 h-6 border-2 border-primary-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
        </div>
        <div v-else-if="notificationsStore.notifications.length === 0" class="px-4 py-8 text-center text-gray-500 text-sm">Нет уведомлений</div>
        <div v-for="notification in notificationsStore.notifications" :key="notification.id" class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer" :class="{ 'bg-blue-50 dark:bg-blue-900/20': !notification.is_read }" @click="markAsRead(notification.id)">
          <div class="flex items-start gap-3">
            <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm" :class="{ 'bg-green-100 text-green-600': notification.type === 'task_assigned', 'bg-amber-100 text-amber-600': notification.type === 'task_due_soon', 'bg-blue-100 text-blue-600': notification.type === 'comment_added', 'bg-purple-100 text-purple-600': notification.type === 'project_invite' }">
              <span v-if="notification.type === 'task_assigned'">✓</span>
              <span v-else-if="notification.type === 'task_due_soon'">⏰</span>
              <span v-else-if="notification.type === 'comment_added'">💬</span>
              <span v-else-if="notification.type === 'project_invite'">👥</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate">{{ notification.title }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ notification.message }}</p>
              <p class="text-xs text-gray-400 mt-1">{{ formatDueRelative(notification.created_at) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

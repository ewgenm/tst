<template>
  <div id="app" class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <!-- Toast -->
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="transform -translate-y-2 opacity-0"
      enter-to-class="transform translate-y-0 opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="transform translate-y-0 opacity-100"
      leave-to-class="transform -translate-y-2 opacity-0"
    >
      <div
        v-if="uiStore.toast"
        class="fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg max-w-sm flex items-start gap-3"
        :class="{
          'bg-green-500 text-white': uiStore.toast.type === 'success',
          'bg-red-500 text-white': uiStore.toast.type === 'error',
          'bg-amber-500 text-white': uiStore.toast.type === 'warning',
          'bg-blue-500 text-white': uiStore.toast.type === 'info',
        }"
      >
        <span class="text-lg flex-shrink-0">
          <span v-if="uiStore.toast.type === 'success'">✅</span>
          <span v-else-if="uiStore.toast.type === 'error'">❌</span>
          <span v-else-if="uiStore.toast.type === 'warning'">⚠️</span>
          <span v-else>ℹ️</span>
        </span>
        <p class="text-sm font-medium flex-1">{{ uiStore.toast.message }}</p>
        <button @click="uiStore.hideToast" class="flex-shrink-0 text-white/80 hover:text-white">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </Transition>

    <!-- Router View -->
    <router-view />
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUIStore } from '@/stores/ui'
import { useTasksStore } from '@/stores/tasks'
import { useNotificationsStore } from '@/stores/notifications'
import { useProjectsStore } from '@/stores/projects'
import { useWebSocket } from '@/composables/useWebSocket'

const authStore = useAuthStore()
const uiStore = useUIStore()
const tasksStore = useTasksStore()
const notificationsStore = useNotificationsStore()
const projectsStore = useProjectsStore()
const ws = useWebSocket()

onMounted(async () => {
  await authStore.fetchMe()

  if (authStore.isAuthenticated && authStore.user) {
    ws.init()
    tasksStore.setupRealtime(authStore.user.id)
    notificationsStore.setupRealtime()
    projectsStore.fetchProjects().catch(() => {})
  }
})

onUnmounted(() => { ws.disconnect() })

window.addEventListener('auth:unauthorized', () => {
  authStore.logout()
  ws.disconnect()
  window.location.href = '/login'
})
</script>

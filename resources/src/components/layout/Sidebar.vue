<!-- ============================================================
Sidebar компонент — ТЗ №2 v1.1 Этап 2
============================================================ -->

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useUIStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { useTasksStore } from '@/stores/tasks'

const uiStore = useUIStore()
const authStore = useAuthStore()
const tasksStore = useTasksStore()
const route = useRoute()

const navItems = computed(() => [
  { path: '/', icon: '📥', label: 'Входящие', count: tasksStore.inboxTasks.length },
  { path: '/today', icon: '📅', label: 'Сегодня', count: tasksStore.todayTasks.length },
  { path: '/projects', icon: '📁', label: 'Проекты' },
  { path: '/habits', icon: '✨', label: 'Привычки' },
])

function isActive(path: string): boolean {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}

async function handleLogout() {
  await authStore.logout()
  window.location.href = '/login'
}
</script>

<template>
  <aside
    class="bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col transition-all duration-300"
    :style="{ width: uiStore.sidebarCollapsed ? '4rem' : '16rem' }"
  >
    <!-- Logo -->
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
      <router-link to="/" class="flex items-center gap-3">
        <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center text-white font-bold text-sm flex-shrink-0">TS</div>
        <h1 v-if="!uiStore.sidebarCollapsed" class="text-xl font-bold text-primary-600 dark:text-primary-500">TaskSync</h1>
      </router-link>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
      <router-link
        v-for="item in navItems" :key="item.path" :to="item.path"
        class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors"
        :class="[
          isActive(item.path) ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 font-medium' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300',
          uiStore.sidebarCollapsed ? 'justify-center' : '',
        ]"
      >
        <span class="text-xl flex-shrink-0">{{ item.icon }}</span>
        <span v-if="!uiStore.sidebarCollapsed" class="flex-1 truncate">{{ item.label }}</span>
        <span v-if="!uiStore.sidebarCollapsed && item.count !== undefined && item.count > 0" class="text-xs bg-gray-200 dark:bg-gray-600 px-2 py-0.5 rounded-full font-medium">{{ item.count > 99 ? '99+' : item.count }}</span>
      </router-link>
    </nav>

    <!-- Footer -->
    <div class="p-3 border-t border-gray-200 dark:border-gray-700 space-y-1">
      <router-link to="/settings" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors" :class="{ 'justify-center': uiStore.sidebarCollapsed }">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span v-if="!uiStore.sidebarCollapsed">Настройки</span>
      </router-link>
      <button @click="handleLogout" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 transition-colors" :class="{ 'justify-center': uiStore.sidebarCollapsed }">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        <span v-if="!uiStore.sidebarCollapsed">Выйти</span>
      </button>
    </div>
  </aside>
</template>

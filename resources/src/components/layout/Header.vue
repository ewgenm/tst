<!-- ============================================================
Header компонент — ТЗ №2 v1.1 Этап 2
============================================================ -->

<script setup lang="ts">
import { computed } from 'vue'
import { useUIStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import SearchBar from '@/components/features/SearchBar.vue'
import NotificationBell from '@/components/features/NotificationBell.vue'

const uiStore = useUIStore()
const authStore = useAuthStore()

const themeIcon = computed(() => {
  if (uiStore.theme === 'system') return '💻'
  return uiStore.theme === 'dark' ? '🌙' : '☀️'
})

function toggleTheme() {
  const themes: Array<'light' | 'dark' | 'system'> = ['light', 'dark', 'system']
  const currentIndex = themes.indexOf(uiStore.theme)
  uiStore.setTheme(themes[(currentIndex + 1) % themes.length])
}
</script>

<template>
  <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <button @click="uiStore.toggleSidebar" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="Меню">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <SearchBar />
      </div>
      <div class="flex items-center gap-3">
        <button @click="toggleTheme" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" :title="`Тема: ${uiStore.theme}`">
          <span class="text-xl">{{ themeIcon }}</span>
        </button>
        <NotificationBell />
        <div v-if="authStore.user" class="flex items-center gap-2 ml-2">
          <div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">{{ authStore.user.name.charAt(0).toUpperCase() }}</div>
          <span class="text-sm font-medium hidden md:inline">{{ authStore.user.name }}</span>
        </div>
      </div>
    </div>
  </header>
</template>

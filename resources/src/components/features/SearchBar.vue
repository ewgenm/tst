<!-- ============================================================
SearchBar компонент с debounce (CRITICAL FIX #1)
============================================================ -->

<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSearchStore } from '@/stores/search'

const router = useRouter()
const searchStore = useSearchStore()
const query = ref('')
const inputRef = ref<HTMLInputElement | null>(null)

let debounceTimer: ReturnType<typeof setTimeout> | null = null

watch(query, (newQuery) => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    if (newQuery.trim()) searchStore.search(newQuery)
    else searchStore.clearResults()
  }, 300)
})

function navigateToResults() {
  if (query.value.trim()) router.push({ path: '/search', query: { q: query.value } })
}

function handleKeydown(event: KeyboardEvent) {
  if (event.key === '/' && document.activeElement?.tagName !== 'INPUT') {
    event.preventDefault()
    inputRef.value?.focus()
  }
}

onMounted(() => window.addEventListener('keydown', handleKeydown))
onUnmounted(() => window.removeEventListener('keydown', handleKeydown))
</script>

<template>
  <div class="relative">
    <input
      ref="inputRef"
      v-model="query"
      type="text"
      placeholder="Поиск... (нажмите /)"
      class="w-64 pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
      @keyup.enter="navigateToResults"
    />
    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
    </svg>
    <div v-if="searchStore.isLoading" class="absolute right-3 top-2.5">
      <div class="w-4 h-4 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
    </div>
  </div>
</template>

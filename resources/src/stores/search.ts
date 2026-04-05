// ============================================================
// Search Store (CRITICAL FIX #1) — ТЗ №2 v1.1 раздел 3.6
// ============================================================

import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { SearchResult } from '@/types'
import { apiClient } from '@/api/client'
import { endpoints } from '@/api/endpoints'

export const useSearchStore = defineStore('search', () => {
  const results = ref<SearchResult | null>(null)
  const isLoading = ref(false)
  const query = ref('')

  async function search(searchQuery: string, types: string[] = ['tasks', 'projects', 'habits']) {
    if (!searchQuery.trim()) { results.value = null; return }
    isLoading.value = true
    query.value = searchQuery
    try {
      const response = await apiClient.get(endpoints.search, {
        params: { q: searchQuery, types: types.join(',') }
      })
      results.value = response.data.data
    } finally { isLoading.value = false }
  }

  function clearResults() { results.value = null; query.value = '' }

  return { results, isLoading, query, search, clearResults }
})

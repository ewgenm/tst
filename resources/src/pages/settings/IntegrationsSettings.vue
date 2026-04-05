<!-- IntegrationsSettings (CRITICAL FIX #9) -->
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiClient } from '@/api/client'
import { endpoints } from '@/api/endpoints'
import { useToast } from '@/composables/useToast'

interface Integration { provider: string; name: string; description: string; icon: string; connected: boolean; connected_at: string | null }
const { show: showToast } = useToast()
const integrations = ref<Integration[]>([])
const isLoading = ref(false)

onMounted(async () => {
  isLoading.value = true
  try {
    const response = await apiClient.get(endpoints.integrations)
    integrations.value = response.data.data || [
      { provider: 'telegram', name: 'Telegram', description: 'Получайте уведомления о задачах', icon: '✈️', connected: false, connected_at: null },
      { provider: 'google', name: 'Google Calendar', description: 'Синхронизация задач с календарём', icon: '📅', connected: false, connected_at: null },
      { provider: 'github', name: 'GitHub', description: 'Привязка коммитов и PR к задачам', icon: '🐙', connected: false, connected_at: null },
    ]
  } finally { isLoading.value = false }
})

async function connect(provider: string) { window.location.href = `/api/v1/integrations/${provider}/connect` }
async function disconnect(provider: string) { try { await apiClient.delete(endpoints.integrationDisconnect(provider)); const i = integrations.value.find(x => x.provider === provider); if (i) i.connected = false; showToast('Интеграция отключена', 'success') } catch { showToast('Ошибка отключения', 'error') } }
</script>

<template>
  <div class="max-w-4xl"><h2 class="text-xl font-semibold mb-6">🔌 Интеграции</h2><div v-if="isLoading" class="text-center py-12"><div class="w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full animate-spin mx-auto"></div></div><div v-else class="space-y-4"><div v-for="integration in integrations" :key="integration.provider" class="flex items-center justify-between p-6 bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700"><div class="flex items-start gap-4"><div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center text-2xl">{{ integration.icon }}</div><div><h3 class="font-semibold text-lg">{{ integration.name }}</h3><p class="text-sm text-gray-500 mt-1">{{ integration.description }}</p></div></div><div><button v-if="integration.connected" @click="disconnect(integration.provider)" class="px-4 py-2 text-sm border border-red-500 text-red-500 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20">Отключить</button><button v-else @click="connect(integration.provider)" class="px-4 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700">Подключить</button></div></div></div></div>
</template>

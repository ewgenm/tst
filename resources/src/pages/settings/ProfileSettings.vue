<!-- ProfileSettings -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUIStore } from '@/stores/ui'
import { useToast } from '@/composables/useToast'

const authStore = useAuthStore()
const uiStore = useUIStore()
const { show: showToast } = useToast()
const name = ref(authStore.user?.name || '')
const email = ref(authStore.user?.email || '')
const timezone = ref(authStore.user?.timezone || 'Europe/Moscow')
const locale = ref(authStore.user?.locale || 'ru')
const theme = computed({ get: () => uiStore.theme, set: (v: 'light' | 'dark' | 'system') => uiStore.setTheme(v) })
const themeOptions = [{ value: 'light', label: '☀️ Светлая' }, { value: 'dark', label: '🌙 Тёмная' }, { value: 'system', label: '💻 Системная' }]
const timezoneOptions = ['Europe/Moscow', 'Europe/Samara', 'Asia/Yekaterinburg', 'Asia/Omsk', 'Asia/Novosibirsk', 'Asia/Vladivostok', 'Asia/Kamchatka', 'UTC']

async function saveProfile() {
  try { await authStore.updateProfile({ name: name.value, timezone: timezone.value, locale: locale.value, theme: uiStore.theme }); showToast('Профиль обновлён', 'success') } catch { showToast('Не удалось обновить профиль', 'error') }
}
</script>

<template>
  <div class="max-w-2xl"><h2 class="text-xl font-semibold mb-6">Профиль</h2><form @submit.prevent="saveProfile" class="space-y-6"><div><label class="block text-sm font-medium mb-1">Имя</label><input v-model="name" type="text" required class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" /></div><div><label class="block text-sm font-medium mb-1">Email</label><input v-model="email" type="email" readonly class="w-full px-3 py-2 border rounded-lg bg-gray-100 dark:bg-gray-800 cursor-not-allowed" /></div><div><label class="block text-sm font-medium mb-1">Часовой пояс</label><select v-model="timezone" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"><option v-for="tz in timezoneOptions" :key="tz" :value="tz">{{ tz }}</option></select></div><div><label class="block text-sm font-medium mb-1">Язык</label><select v-model="locale" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"><option value="ru">🇷🇺 Русский</option><option value="en">🇺🇸 English</option></select></div><div><label class="block text-sm font-medium mb-2">Тема оформления</label><div class="grid grid-cols-3 gap-3"><button v-for="option in themeOptions" :key="option.value" type="button" @click="theme = option.value as any" class="p-4 border-2 rounded-lg transition-colors text-center" :class="theme === option.value ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'"><div class="text-2xl mb-2">{{ option.label }}</div></button></div></div><button type="submit" :disabled="authStore.isLoading" class="w-full bg-primary-600 text-white py-2 rounded-lg hover:bg-primary-700 disabled:opacity-50">{{ authStore.isLoading ? 'Сохранение...' : 'Сохранить' }}</button></form></div>
</template>

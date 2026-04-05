<!-- LoginPage -->
<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const email = ref('')
const password = ref('')

async function handleLogin() {
  const result = await authStore.login(email.value, password.value)
  if (result.success) router.push('/')
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-gray-900 dark:via-gray-900 dark:to-indigo-900 px-4 py-12">
    <div class="max-w-md w-full bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-8">
      <div class="text-center mb-8"><div class="inline-flex items-center justify-center w-16 h-16 bg-primary-600 rounded-2xl text-white font-bold text-2xl mb-4">TS</div><h1 class="text-3xl font-bold text-gray-900 dark:text-white">Вход в TaskSync</h1></div>
      <form @submit.prevent="handleLogin" class="space-y-5">
        <div><label class="block text-sm font-medium mb-1">Email</label><input v-model="email" type="email" required class="w-full px-4 py-3 border rounded-xl bg-gray-50 dark:bg-gray-700 dark:border-gray-600 focus:ring-2 focus:ring-primary-500" placeholder="you@example.com" /></div>
        <div><label class="block text-sm font-medium mb-1">Пароль</label><input v-model="password" type="password" required class="w-full px-4 py-3 border rounded-xl bg-gray-50 dark:bg-gray-700 dark:border-gray-600 focus:ring-2 focus:ring-primary-500" placeholder="••••••••" /></div>
        <p v-if="authStore.error" class="text-red-500 text-sm">{{ authStore.error }}</p>
        <button type="submit" :disabled="authStore.isLoading" class="w-full bg-primary-600 text-white py-3 rounded-xl font-semibold hover:bg-primary-700 disabled:opacity-50 flex items-center justify-center gap-2 shadow-lg shadow-primary-600/20">
          <svg v-if="authStore.isLoading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
          <span v-else>{{ authStore.isLoading ? 'Вход...' : 'Войти' }}</span>
        </button>
      </form>
      <p class="text-center text-sm text-gray-600 dark:text-gray-400 mt-6">Нет аккаунта? <router-link to="/register" class="text-primary-600 hover:underline font-semibold">Зарегистрироваться</router-link></p>
    </div>
  </div>
</template>

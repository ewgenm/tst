<!-- RegisterPage (Production-ready) -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const acceptTerms = ref(false)
const errors = ref<Record<string, string[]>>({})
const showPassword = ref(false)

const passwordStrength = computed(() => {
  const pwd = password.value; if (pwd.length === 0) return { level: 0, label: '', color: '' }
  let score = 0; if (pwd.length >= 8) score++; if (pwd.length >= 12) score++; if (/[A-Z]/.test(pwd)) score++; if (/[0-9]/.test(pwd)) score++; if (/[^A-Za-z0-9]/.test(pwd)) score++
  if (score <= 2) return { level: 1, label: 'Слабый', color: 'bg-red-500' }; if (score <= 4) return { level: 2, label: 'Средний', color: 'bg-amber-500' }; return { level: 3, label: 'Сильный', color: 'bg-green-500' }
})
const passwordsMatch = computed(() => passwordConfirmation.value.length === 0 || password.value === passwordConfirmation.value)
const isFormValid = computed(() => name.value.trim().length >= 2 && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value) && password.value.length >= 8 && passwordsMatch.value && acceptTerms.value)

async function handleRegister() {
  errors.value = {}; if (!isFormValid.value) return
  const result = await authStore.register(name.value.trim(), email.value.trim(), password.value)
  if (result.success) router.push('/')
}

function handleValidationErrors(event: Event) { errors.value = (event as CustomEvent<Record<string, string[]>>).detail }
import { onMounted, onUnmounted } from 'vue'
onMounted(() => window.addEventListener('validation-errors', handleValidationErrors))
onUnmounted(() => window.removeEventListener('validation-errors', handleValidationErrors))
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-gray-900 dark:via-gray-900 dark:to-indigo-900 px-4 py-12">
    <div class="max-w-md w-full">
      <div class="text-center mb-8"><div class="inline-flex items-center justify-center w-16 h-16 bg-primary-600 rounded-2xl text-white font-bold text-2xl mb-4 shadow-lg">TS</div><h1 class="text-3xl font-bold text-gray-900 dark:text-white">Создать аккаунт</h1><p class="text-gray-600 dark:text-gray-400 mt-2">Начните управлять задачами бесплатно</p></div>
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-8">
        <form @submit.prevent="handleRegister" class="space-y-5" novalidate>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Имя</label><input v-model="name" type="text" required minlength="2" placeholder="Ваше имя" class="w-full px-4 py-3 border rounded-xl bg-gray-50 dark:bg-gray-700 dark:border-gray-600 focus:ring-2 focus:ring-primary-500" :class="{ 'border-red-500': errors.name }" autofocus /><p v-if="errors.name" class="text-red-500 text-xs mt-1.5">{{ errors.name[0] }}</p></div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label><input v-model="email" type="email" required placeholder="you@example.com" class="w-full px-4 py-3 border rounded-xl bg-gray-50 dark:bg-gray-700 dark:border-gray-600 focus:ring-2 focus:ring-primary-500" :class="{ 'border-red-500': errors.email }" /><p v-if="errors.email" class="text-red-500 text-xs mt-1.5">{{ errors.email[0] }}</p></div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Пароль</label>
            <div class="relative"><input v-model="password" :type="showPassword ? 'text' : 'password'" required minlength="8" placeholder="Минимум 8 символов" class="w-full px-4 py-3 pr-12 border rounded-xl bg-gray-50 dark:bg-gray-700 dark:border-gray-600 focus:ring-2 focus:ring-primary-500" :class="{ 'border-red-500': errors.password }" /><button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" @click="showPassword = !showPassword"><svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg><svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg></button></div>
            <div v-if="password.length > 0" class="mt-2"><div class="flex items-center justify-between mb-1"><div class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mr-2"><div class="h-full transition-all duration-300 rounded-full" :class="[passwordStrength.color, { 'w-1/3': passwordStrength.level === 1, 'w-2/3': passwordStrength.level === 2, 'w-full': passwordStrength.level === 3 }]" /></div><span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ passwordStrength.label }}</span></div></div>
            <p v-if="errors.password" class="text-red-500 text-xs mt-1.5">{{ errors.password[0] }}</p>
          </div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Подтверждение пароля</label><input v-model="passwordConfirmation" type="password" required placeholder="Повторите пароль" class="w-full px-4 py-3 border rounded-xl bg-gray-50 dark:bg-gray-700 dark:border-gray-600 focus:ring-2 focus:ring-primary-500" :class="{ 'border-red-500': !passwordsMatch || errors.password_confirmation }" /><p v-if="!passwordsMatch && passwordConfirmation.length > 0" class="text-red-500 text-xs mt-1.5">Пароли не совпадают</p></div>
          <div class="flex items-start gap-3"><input v-model="acceptTerms" type="checkbox" class="w-4 h-4 mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500" /><label class="text-sm text-gray-600 dark:text-gray-400">Я согласен с <a href="#" class="text-primary-600 hover:underline font-medium">Условиями использования</a> и <a href="#" class="text-primary-600 hover:underline font-medium">Политикой конфиденциальности</a></label></div>
          <div v-if="authStore.error" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-600 dark:text-red-400 text-sm">{{ authStore.error }}</p></div>
          <button type="submit" :disabled="!isFormValid || authStore.isLoading" class="w-full bg-primary-600 text-white py-3 rounded-xl font-semibold hover:bg-primary-700 disabled:opacity-50 transition-all duration-200 flex items-center justify-center gap-2 shadow-lg shadow-primary-600/20"><svg v-if="authStore.isLoading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg><span v-else>{{ authStore.isLoading ? 'Создание аккаунта...' : 'Создать аккаунт' }}</span></button>
        </form>
        <div class="relative my-6"><div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200 dark:border-gray-700"></div></div><div class="relative flex justify-center text-sm"><span class="px-4 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400">или</span></div></div>
        <div class="grid grid-cols-2 gap-3"><button type="button" class="flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"><svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>Google</button><button type="button" class="flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>GitHub</button></div>
      </div>
      <p class="text-center text-sm text-gray-600 dark:text-gray-400 mt-6">Уже есть аккаунт? <router-link to="/login" class="text-primary-600 hover:underline font-semibold">Войти</router-link></p>
    </div>
  </div>
</template>

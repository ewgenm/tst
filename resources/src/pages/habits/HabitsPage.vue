<!-- HabitsPage -->
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useHabitsStore } from '@/stores/habits'
import { useDateFormatter } from '@/composables/useDateFormatter'

const habitsStore = useHabitsStore()
const { formatDueDate } = useDateFormatter()
const showHabitForm = ref(false)
const newHabitName = ref('')
const newHabitColor = ref('#8B5CF6')
const newHabitFrequency = ref<'daily' | 'weekly' | 'custom'>('daily')

const weekDays = computed(() => { const days = []; const today = new Date(); const start = new Date(today); start.setDate(today.getDate() - today.getDay()); for (let i = 0; i < 7; i++) { const d = new Date(start); d.setDate(start.getDate() + i); days.push(d) } return days })
const dayNames = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб']

onMounted(async () => { await habitsStore.fetchHabits() })
async function toggleHabit(habitId: number) { await habitsStore.logCompletion(habitId, new Date().toISOString().split('T')[0]) }
async function createHabit() { if (!newHabitName.value.trim()) return; await habitsStore.createHabit({ name: newHabitName.value, color: newHabitColor.value, frequency: newHabitFrequency.value, target_days: [1,2,3,4,5] }); showHabitForm.value = false; newHabitName.value = '' }
function isCompletedToday(_habit: any): boolean { return false }
const frequencyIcons: Record<string, string> = { daily: '📅', weekly: '📆', custom: '🔧' }
const frequencyLabels: Record<string, string> = { daily: 'Ежедневно', weekly: 'Еженедельно', custom: 'Произвольно' }
</script>

<template>
  <div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6"><div><h1 class="text-2xl font-bold">✨ Привычки</h1><p class="text-sm text-gray-500 mt-1">Трекер привычек ({{ habitsStore.habits.length }})</p></div><button @click="showHabitForm = !showHabitForm" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>Новая привычка</button></div>
    <div v-if="showHabitForm" class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-200 dark:border-gray-700">
      <h2 class="text-lg font-semibold mb-4">Создать привычку</h2>
      <form @submit.prevent="createHabit" class="space-y-4">
        <div><label class="block text-sm font-medium mb-1">Название</label><input v-model="newHabitName" type="text" required class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" placeholder="Например: Медитация 10 минут" /></div>
        <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium mb-1">Цвет</label><input v-model="newHabitColor" type="color" class="w-full h-10 rounded-lg border border-gray-300 dark:border-gray-600 cursor-pointer" /></div><div><label class="block text-sm font-medium mb-1">Частота</label><select v-model="newHabitFrequency" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"><option value="daily">Ежедневно</option><option value="weekly">Еженедельно</option><option value="custom">Произвольно</option></select></div></div>
        <div class="flex gap-2"><button type="submit" :disabled="habitsStore.isLoading" class="flex-1 bg-primary-600 text-white py-2 rounded-lg disabled:opacity-50">Создать</button><button type="button" @click="showHabitForm = false" class="px-4 py-2 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Отмена</button></div>
      </form>
    </div>
    <div v-if="habitsStore.isLoading" class="text-center py-12"><div class="w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full animate-spin mx-auto"></div></div>
    <div v-else-if="habitsStore.habits.length === 0" class="text-center py-12 text-gray-500"><svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg><p class="text-lg font-medium">Нет привычек</p></div>
    <div v-else class="space-y-4">
      <div v-for="habit in habitsStore.habits" :key="habit.id" class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-start justify-between">
          <div class="flex items-start gap-4"><div class="w-12 h-12 rounded-lg flex items-center justify-center text-white text-xl" :style="{ backgroundColor: habit.color }">{{ habit.icon || '✨' }}</div><div><h3 class="text-lg font-semibold">{{ habit.name }}</h3><div class="flex items-center gap-3 mt-1 text-sm text-gray-500"><span>{{ frequencyIcons[habit.frequency] }} {{ frequencyLabels[habit.frequency] }}</span><span>🔥 {{ habit.current_streak }} дн.</span><span>🏆 {{ habit.best_streak }} (рекорд)</span></div></div></div>
          <button @click="toggleHabit(habit.id)" :disabled="habitsStore.isLogging" class="px-4 py-2 rounded-lg font-medium transition-colors" :class="isCompletedToday(habit) ? 'bg-green-500 text-white' : 'bg-gray-100 dark:bg-gray-700 hover:bg-green-500 hover:text-white'">{{ isCompletedToday(habit) ? '✓' : 'Отметить' }}</button>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700"><div class="flex justify-between gap-2"><div v-for="(date, index) in weekDays" :key="index" class="flex-1 text-center"><div class="text-xs text-gray-500 mb-1">{{ dayNames[index] }}</div><div class="w-8 h-8 mx-auto rounded-full flex items-center justify-center text-sm" :class="isCompletedToday(habit) && date.toDateString() === new Date().toDateString() ? 'bg-green-500 text-white' : 'bg-gray-100 dark:bg-gray-700'">{{ date.getDate() }}</div></div></div></div>
      </div>
    </div>
  </div>
</template>

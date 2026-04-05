<!-- HabitsPage — Трекер привычек с календарём и статистикой -->
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useHabitsStore } from '@/stores/habits'
import { useAuthStore } from '@/stores/auth'
import { useDateFormatter } from '@/composables/useDateFormatter'
import { useToast } from '@/composables/useToast'

const habitsStore = useHabitsStore()
const authStore = useAuthStore()
const { formatDueDate } = useDateFormatter()
const { show: showToast } = useToast()

const showHabitForm = ref(false)
const expandedHabitId = ref<number | null>(null)
const newHabitName = ref('')
const newHabitColor = ref('#8B5CF6')
const newHabitIcon = ref('✨')
const newHabitFrequency = ref<'daily' | 'weekly' | 'custom'>('daily')

const weekDays = computed(() => {
  const days = []
  const today = new Date()
  const dayOfWeek = today.getDay()
  const startOfWeek = new Date(today)
  startOfWeek.setDate(today.getDate() - dayOfWeek) // Воскресенье

  for (let i = 0; i < 7; i++) {
    const date = new Date(startOfWeek)
    date.setDate(startOfWeek.getDate() + i)
    days.push(date)
  }
  return days
})

const dayNames = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб']

const quickIcons = ['✨', '🧘', '📚', '', '', '💧', '', '🍎', '🎨', '', '', '']
const colorOptions = [
  '#8B5CF6', '#6366F1', '#3B82F6', '#06B6D4',
  '#10B981', '#F59E0B', '#EF4444', '#EC4899',
  '#84CC16', '#14B8A6', '#F97316', '#A855F7'
]

onMounted(async () => {
  await habitsStore.fetchHabits()
  // Загружаем статистику для каждой привычки
  for (const habit of habitsStore.habits) {
    await habitsStore.fetchHabitStats(habit.id)
  }
})

function toggleExpand(habitId: number) {
  expandedHabitId.value = expandedHabitId.value === habitId ? null : habitId
}

function isCompletedToday(habit: any): boolean {
  const today = new Date().toISOString().split('T')[0]
  return habitsStore.isCompletedOnDate(habit.id, today)
}

function getCompletionRate(habit: any, days: number): number {
  const completions = habitsStore.completions[habit.id] || []
  const today = new Date()
  const startDate = new Date(today)
  startDate.setDate(today.getDate() - days)

  let completedDays = 0
  let totalDays = 0
  const d = new Date(startDate)
  while (d <= today) {
    totalDays++
    const dateStr = d.toISOString().split('T')[0]
    if (completions.some((c: any) => c.completed_date === dateStr)) {
      completedDays++
    }
    d.setDate(d.getDate() + 1)
  }

  return totalDays > 0 ? Math.round((completedDays / totalDays) * 100) : 0
}

async function toggleHabit(habitId: number) {
  const today = new Date().toISOString().split('T')[0]
  const alreadyDone = habitsStore.isCompletedOnDate(habitId, today)

  if (alreadyDone) {
    showToast('Привычка уже выполнена сегодня', 'warning')
    return
  }

  await habitsStore.logCompletion(habitId, today)
  // Обновляем статистику
  await habitsStore.fetchHabitStats(habitId)
  // Также обновляем данные привычки
  await habitsStore.fetchHabits()
}

async function createHabit() {
  if (!newHabitName.value.trim()) return
  await habitsStore.createHabit({
    name: newHabitName.value,
    color: newHabitColor.value,
    icon: newHabitIcon.value,
    frequency: newHabitFrequency.value,
    target_days: [1, 2, 3, 4, 5],
  })
  showHabitForm.value = false
  newHabitName.value = ''
}

function getStreakColor(streak: number): string {
  if (streak >= 30) return 'text-emerald-500'
  if (streak >= 14) return 'text-blue-500'
  if (streak >= 7) return 'text-amber-500'
  if (streak >= 3) return 'text-orange-500'
  return 'text-gray-400'
}

function getStreakLabel(streak: number): string {
  if (streak >= 30) return '🏆 Мастер'
  if (streak >= 14) return '🔥 Отлично'
  if (streak >= 7) return '💪 Хорошо'
  if (streak >= 3) return '👍 Начало'
  return '🌱 Старт'
}
</script>

<template>
  <div class="max-w-5xl mx-auto">
    <!-- Заголовок -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold">✨ Привычки</h1>
        <p class="text-sm text-gray-500 mt-1">Отслеживайте свои привычки и формируйте полезные ритуалы</p>
      </div>
      <button
        @click="showHabitForm = !showHabitForm"
        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Новая привычка
      </button>
    </div>

    <!-- Форма создания привычки -->
    <div v-if="showHabitForm" class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-200 dark:border-gray-700">
      <h2 class="text-lg font-semibold mb-4">Создать привычку</h2>
      <form @submit.prevent="createHabit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Название</label>
          <input
            v-model="newHabitName"
            type="text"
            required
            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
            placeholder="Например: Медитация 10 минут"
          />
        </div>

        <!-- Иконка -->
        <div>
          <label class="block text-sm font-medium mb-1">Иконка</label>
          <div class="flex gap-2 flex-wrap">
            <button
              v-for="icon in quickIcons" :key="icon"
              type="button"
              @click="newHabitIcon = icon"
              class="w-10 h-10 rounded-lg text-lg flex items-center justify-center"
              :class="newHabitIcon === icon ? 'bg-primary-100 dark:bg-primary-900/30 border-2 border-primary-500' : 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200'"
            >
              {{ icon }}
            </button>
          </div>
        </div>

        <!-- Цвет -->
        <div>
          <label class="block text-sm font-medium mb-1">Цвет</label>
          <div class="flex gap-2 flex-wrap">
            <button
              v-for="color in colorOptions" :key="color"
              type="button"
              @click="newHabitColor = color"
              class="w-8 h-8 rounded-full transition-transform hover:scale-110"
              :class="newHabitColor === color ? 'ring-2 ring-offset-2 ring-primary-500 scale-110' : ''"
              :style="{ backgroundColor: color }"
            />
          </div>
        </div>

        <!-- Частота -->
        <div>
          <label class="block text-sm font-medium mb-1">Частота</label>
          <select v-model="newHabitFrequency" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600">
            <option value="daily">Ежедневно</option>
            <option value="weekly">Еженедельно</option>
            <option value="custom">Произвольно</option>
          </select>
        </div>

        <div class="flex gap-2">
          <button type="submit" :disabled="habitsStore.isLoading" class="flex-1 bg-primary-600 text-white py-2 rounded-lg disabled:opacity-50">Создать</button>
          <button type="button" @click="showHabitForm = false" class="px-4 py-2 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Отмена</button>
        </div>
      </form>
    </div>

    <!-- Список привычек -->
    <div v-if="habitsStore.isLoading" class="text-center py-12">
      <div class="w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
    </div>

    <div v-else-if="habitsStore.habits.length === 0" class="text-center py-12 text-gray-500">
      <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
      </svg>
      <p class="text-lg font-medium">Нет привычек</p>
      <p class="text-sm mt-2">Создайте первую привычку для отслеживания</p>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="habit in habitsStore.habits"
        :key="habit.id"
        class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden"
      >
        <!-- Шапка привычки -->
        <div
          class="p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors"
          @click="toggleExpand(habit.id)"
        >
          <div class="flex items-start justify-between">
            <div class="flex items-start gap-4">
              <!-- Иконка -->
              <div
                class="w-12 h-12 rounded-lg flex items-center justify-center text-white text-xl flex-shrink-0"
                :style="{ backgroundColor: habit.color }"
              >
                {{ habit.icon || '✨' }}
              </div>

              <!-- Информация -->
              <div>
                <h3 class="text-lg font-semibold">{{ habit.name }}</h3>
                <div class="flex items-center gap-4 mt-1 text-sm text-gray-500">
                  <span>🔥 {{ habit.current_streak || 0 }} дн.</span>
                  <span>🏆 {{ habit.best_streak || 0 }} (рекорд)</span>
                  <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs">
                    {{ habit.frequency === 'daily' ? 'Ежедневно' : habit.frequency === 'weekly' ? 'Еженедельно' : 'Произвольно' }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Кнопка выполнения -->
            <div class="flex items-center gap-3">
              <button
                @click.stop="toggleHabit(habit.id)"
                class="px-4 py-2 rounded-lg font-medium transition-all"
                :class="isCompletedToday(habit)
                  ? 'bg-green-500 text-white hover:bg-green-600'
                  : 'bg-gray-100 dark:bg-gray-700 hover:bg-green-500 hover:text-white'"
              >
                {{ isCompletedToday(habit) ? '✓ Выполнено' : 'Отметить' }}
              </button>
              <svg
                class="w-5 h-5 text-gray-400 transition-transform"
                :class="{ 'rotate-180': expandedHabitId === habit.id }"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Развёрнутая статистика -->
        <div v-if="expandedHabitId === habit.id" class="border-t border-gray-200 dark:border-gray-700 p-4 space-y-6">
          <!-- Календарь на неделю -->
          <div>
            <h4 class="text-sm font-medium text-gray-500 mb-3">Эта неделя</h4>
            <div class="flex justify-between gap-2">
              <div
                v-for="(date, index) in weekDays" :key="index"
                class="flex-1 text-center"
              >
                <div class="text-xs text-gray-500 mb-1">{{ dayNames[index] }}</div>
                <div
                  class="w-10 h-10 mx-auto rounded-full flex items-center justify-center text-sm font-medium transition-all"
                  :class="habitsStore.isCompletedOnDate(habit.id, date.toISOString().split('T')[0])
                    ? 'text-white'
                    : date.toDateString() === new Date().toDateString()
                      ? 'ring-2 ring-primary-500 bg-gray-100 dark:bg-gray-700'
                      : 'bg-gray-100 dark:bg-gray-700 text-gray-500'"
                  :style="habitsStore.isCompletedOnDate(habit.id, date.toISOString().split('T')[0]) ? { backgroundColor: habit.color } : {}"
                >
                  {{ date.getDate() }}
                </div>
              </div>
            </div>
          </div>

          <!-- Статистика -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
              <div class="text-2xl font-bold" :class="getStreakColor(habit.current_streak || 0)">{{ habit.current_streak || 0 }}</div>
              <div class="text-xs text-gray-500 mt-1">Дней подряд</div>
              <div class="text-xs mt-1">{{ getStreakLabel(habit.current_streak || 0) }}</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
              <div class="text-2xl font-bold text-blue-500">{{ habit.best_streak || 0 }}</div>
              <div class="text-xs text-gray-500 mt-1">Лучшая серия</div>
              <div class="text-xs mt-1">🏆 Рекорд</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
              <div class="text-2xl font-bold text-amber-500">{{ getCompletionRate(habit, 7) }}%</div>
              <div class="text-xs text-gray-500 mt-1">За неделю</div>
              <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1.5 mt-2">
                <div class="bg-amber-500 h-1.5 rounded-full transition-all" :style="{ width: getCompletionRate(habit, 7) + '%' }" />
              </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
              <div class="text-2xl font-bold text-emerald-500">{{ getCompletionRate(habit, 30) }}%</div>
              <div class="text-xs text-gray-500 mt-1">За месяц</div>
              <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1.5 mt-2">
                <div class="bg-emerald-500 h-1.5 rounded-full transition-all" :style="{ width: getCompletionRate(habit, 30) + '%' }" />
              </div>
            </div>
          </div>

          <!-- Дополнительная статистика -->
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
              <div class="text-lg font-bold text-purple-500">{{ getCompletionRate(habit, 90) }}%</div>
              <div class="text-xs text-gray-500 mt-1">За 3 месяца</div>
              <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1.5 mt-2">
                <div class="bg-purple-500 h-1.5 rounded-full transition-all" :style="{ width: getCompletionRate(habit, 90) + '%' }" />
              </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
              <div class="text-lg font-bold text-pink-500">{{ getCompletionRate(habit, 365) }}%</div>
              <div class="text-xs text-gray-500 mt-1">За год</div>
              <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1.5 mt-2">
                <div class="bg-pink-500 h-1.5 rounded-full transition-all" :style="{ width: getCompletionRate(habit, 365) + '%' }" />
              </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
              <div class="text-lg font-bold text-cyan-500">{{ (habitsStore.completions[habit.id] || []).length }}</div>
              <div class="text-xs text-gray-500 mt-1">Всего выполнений</div>
              <div class="text-xs mt-1">📊 Общий прогресс</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

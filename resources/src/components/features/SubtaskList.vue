<!-- SubtaskList — раскрывающийся список подзадач с прогресс-баром -->
<script setup lang="ts">
import { ref, watch } from 'vue'
import type { Task } from '@/types'
import { useTasksStore } from '@/stores/tasks'
import { useToast } from '@/composables/useToast'

interface Props {
  taskId: number
  subtasksTotal: number
  subtasksCompleted: number
}

const props = defineProps<Props>()

const tasksStore = useTasksStore()
const { show: showToast } = useToast()

const isExpanded = ref(false)
const subtasks = ref<Task[]>([])
const isLoading = ref(false)
const showAddForm = ref(false)
const newSubtaskTitle = ref('')

const progress = ref(0)
const completedCount = ref(0)
const totalCount = ref(0)

// Обновляем прогресс при изменении props
watch(() => [props.subtasksTotal, props.subtasksCompleted], () => {
  progress.value = props.subtasksTotal > 0 
    ? Math.round((props.subtasksCompleted / props.subtasksTotal) * 100) 
    : 0
  completedCount.value = props.subtasksCompleted
  totalCount.value = props.subtasksTotal
}, { immediate: true })

async function toggleExpand() {
  isExpanded.value = !isExpanded.value
  if (isExpanded.value && subtasks.value.length === 0) {
    await fetchSubtasks()
  }
}

async function fetchSubtasks() {
  isLoading.value = true
  try {
    const data = await tasksStore.fetchSubtasks(props.taskId)
    subtasks.value = data
  } finally {
    isLoading.value = false
  }
}

async function addSubtask() {
  if (!newSubtaskTitle.value.trim()) return
  
  const newSubtask = await tasksStore.createSubtask(props.taskId, {
    title: newSubtaskTitle.value,
    status: 'todo',
    priority: 'medium',
  })
  
  subtasks.value.push(newSubtask)
  newSubtaskTitle.value = ''
  showAddForm.value = false
  showToast('Подзадача создана', 'success')
}

async function toggleSubtaskStatus(subtask: Task) {
  await tasksStore.toggleSubtask(props.taskId, subtask.id)
  // Обновляем локальное состояние
  subtask.status = subtask.status === 'done' ? 'todo' : 'done'
}

async function deleteSubtask(subId: number) {
  if (!confirm('Удалить подзадачу?')) return
  
  await tasksStore.deleteSubtask(props.taskId, subId)
  subtasks.value = subtasks.value.filter(s => s.id !== subId)
  showToast('Подзадача удалена', 'success')
}
</script>

<template>
  <div class="mt-1">
    <!-- Прогресс-бар + счётчик -->
    <div
      v-if="totalCount > 0"
      class="inline-flex items-center gap-1.5 cursor-pointer text-xs"
      @click="toggleExpand"
    >
      <!-- Прогресс-бар мини -->
      <div class="w-12 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
        <div
          class="h-full bg-green-500 rounded-full transition-all"
          :style="{ width: progress + '%' }"
        />
      </div>
      <span class="text-gray-500 dark:text-gray-400">
        {{ completedCount }}/{{ totalCount }}
      </span>
      <svg
        class="w-3 h-3 text-gray-400 transition-transform"
        :class="{ 'rotate-180': isExpanded }"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </div>

    <!-- Раскрывающийся список подзадач -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 max-h-0"
      enter-to-class="opacity-100 max-h-96"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="opacity-100 max-h-96"
      leave-to-class="opacity-0 max-h-0"
    >
      <div v-if="isExpanded" class="mt-2 ml-1 space-y-1 overflow-hidden">
        <!-- Loading -->
        <div v-if="isLoading" class="space-y-1">
          <div v-for="i in 2" :key="i" class="flex items-center gap-2 p-2 animate-pulse">
            <div class="w-4 h-4 rounded-full bg-gray-200 dark:bg-gray-700"></div>
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
          </div>
        </div>

        <!-- Список подзадач -->
        <template v-else>
          <div
            v-for="sub in subtasks"
            :key="sub.id"
            class="group/sub flex items-center gap-2 p-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
          >
            <!-- Чекбокс -->
            <button
              @click="toggleSubtaskStatus(sub)"
              class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-colors"
              :class="sub.status === 'done' ? 'bg-green-500 border-green-500' : 'border-gray-300 dark:border-gray-600 hover:border-primary-500'"
            >
              <svg v-if="sub.status === 'done'" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </button>

            <!-- Название -->
            <span
              class="text-xs flex-1 truncate cursor-pointer"
              :class="sub.status === 'done' ? 'line-through text-gray-400' : 'text-gray-700 dark:text-gray-300'"
            >
              {{ sub.title }}
            </span>

            <!-- Приоритет -->
            <span
              v-if="sub.priority && sub.priority !== 'medium'"
              class="w-1.5 h-1.5 rounded-full flex-shrink-0"
              :class="{
                'bg-red-500': sub.priority === 'urgent',
                'bg-amber-500': sub.priority === 'high',
                'bg-gray-400': sub.priority === 'low',
              }"
            />

            <!-- Кнопка удаления -->
            <button
              @click="deleteSubtask(sub.id)"
              class="opacity-0 group-hover/sub:opacity-100 p-0.5 hover:text-red-500 transition-opacity"
              title="Удалить"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </template>

        <!-- Кнопка добавления -->
        <div v-if="!showAddForm">
          <button
            @click="showAddForm = true"
            class="flex items-center gap-1.5 text-xs text-gray-400 hover:text-primary-500 transition-colors p-1"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Добавить подзадачу
          </button>
        </div>

        <!-- Форма добавления -->
        <div v-else class="flex items-center gap-2 p-1">
          <input
            v-model="newSubtaskTitle"
            @keyup.enter="addSubtask"
            @keyup.escape="showAddForm = false; newSubtaskTitle = ''"
            type="text"
            placeholder="Название подзадачи..."
            class="flex-1 text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded focus:outline-none focus:ring-1 focus:ring-primary-500"
            autofocus
          />
          <button
            @click="addSubtask"
            class="p-1 text-green-500 hover:bg-green-50 dark:hover:bg-green-900/20 rounded"
            title="Создать"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </button>
          <button
            @click="showAddForm = false; newSubtaskTitle = ''"
            class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
            title="Отмена"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

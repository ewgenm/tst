<!-- TaskList с Drag & Drop (CRITICAL FIX #8) -->
<script setup lang="ts">
import { ref, watch } from 'vue'
import draggable from 'vuedraggable'
import type { Task } from '@/types'
import TaskItem from './TaskItem.vue'
import { useTasksStore } from '@/stores/tasks'
import { useToast } from '@/composables/useToast'

interface Props { tasks: Task[]; showProject?: boolean; compact?: boolean; emptyMessage?: string; isLoading?: boolean }
const props = withDefaults(defineProps<Props>(), { showProject: false, compact: false, emptyMessage: 'Нет задач', isLoading: false })

const tasksStore = useTasksStore()
const { show: showToast } = useToast()
const isDragging = ref(false)

// Локальная копия для drag-and-drop
const localTasks = ref<Task[]>([...props.tasks])

// Синхронизируем при изменении props
watch(() => props.tasks, (newTasks) => {
  localTasks.value = [...newTasks]
}, { deep: true, immediate: true })

async function handleToggle(id: number) {
  const task = props.tasks.find(t => t.id === id)
  if (!task) return
  if (task.status === 'done') await tasksStore.updateTask(id, { status: 'todo' })
  else await tasksStore.completeTask(id)
}

async function onSortEnd(event: any) {
  isDragging.value = false

  // Получаем задачу которая была перемещена
  const movedTask = localTasks.value[event.newIndex]
  if (!movedTask) return

  // Вычисляем позицию на основе соседних задач
  const prevTask = event.newIndex > 0 ? localTasks.value[event.newIndex - 1] : null
  const nextTask = event.newIndex < localTasks.value.length - 1 ? localTasks.value[event.newIndex + 1] : null

  let newPosition: number
  if (!prevTask && !nextTask) {
    // Единственная задача
    newPosition = 1000
  } else if (!prevTask) {
    // Переместили в начало
    newPosition = (nextTask?.position || 2000) / 2
  } else if (!nextTask) {
    // Переместили в конец
    newPosition = (prevTask?.position || 0) + 1000
  } else {
    // Переместили между задачами
    newPosition = ((prevTask?.position || 0) + (nextTask?.position || 2000)) / 2
  }

  try {
    await tasksStore.reorderTask(movedTask.id, newPosition)
    showToast('Порядок задач обновлён', 'success')
    // Обновляем список задач из сервера для корректного порядка
    await tasksStore.fetchTasks(tasksStore.filters)
  } catch {
    showToast('Не удалось обновить порядок', 'error')
    // Возвращаем исходный порядок
    localTasks.value = [...props.tasks]
  }
}

function handleEdit(id: number) { /* navigate */ }
async function handleDelete(id: number) { if (confirm('Удалить задачу?')) await tasksStore.deleteTask(id) }
</script>

<template>
  <div>
    <!-- Loading skeleton -->
    <div v-if="isLoading" class="space-y-2">
      <div v-for="i in 5" :key="i" class="flex items-start gap-3 p-3 rounded-lg animate-pulse">
        <div class="w-5 h-5 rounded-full bg-gray-200 dark:bg-gray-700 flex-shrink-0"></div>
        <div class="flex-1 space-y-2">
          <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
          <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else-if="localTasks.length === 0" class="text-center py-12 text-gray-500">
      <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
      </svg>
      <p class="text-lg font-medium">{{ emptyMessage }}</p>
    </div>

    <!-- Draggable task list -->
    <draggable
      v-else
      v-model="localTasks"
      item-key="id"
      :animation="200"
      :ghost-class="'opacity-40'"
      :drag-class="'opacity-75'"
      handle=".drag-handle"
      @start="isDragging = true"
      @end="onSortEnd"
    >
      <template #item="{ element }">
        <div class="flex items-start gap-2 group/task" :class="{ 'cursor-grab': !isDragging }">
          <!-- Drag handle - иконка перетаскивания -->
          <div
            class="drag-handle flex items-center justify-center w-6 h-6 mt-1 rounded cursor-grab hover:bg-gray-100 dark:hover:bg-gray-700 opacity-0 group-hover/task:opacity-100 transition-opacity flex-shrink-0"
            :class="{ 'opacity-40': isDragging }"
            title="Перетащить для изменения порядка"
          >
            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
              <path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z" />
            </svg>
          </div>

          <!-- Task item -->
          <div class="flex-1 min-w-0">
            <TaskItem
              :task="element"
              :show-project="showProject"
              :compact="compact"
              @toggle="handleToggle"
              @edit="handleEdit"
              @delete="handleDelete"
            />
          </div>
        </div>
      </template>
    </draggable>

    <!-- Подсказка о drag-and-drop -->
    <div v-if="localTasks.length > 1 && !isLoading" class="mt-2 text-xs text-gray-400 text-center">
      💡 Наведите на задачу и перетащите за иконку для изменения порядка
    </div>
  </div>
</template>

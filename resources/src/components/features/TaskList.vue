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

const emit = defineEmits<{
  'task-click': [id: number]
  'task-toggle': [id: number]
  'task-delete': [id: number]
}>()

const tasksStore = useTasksStore()
const { show: showToast } = useToast()
const isDragging = ref(false)
const dragIndex = ref(-1)

// Используем props.tasks напрямую - watch для реактивности
const localTasks = ref<Task[]>([])

watch(() => props.tasks, (newTasks) => {
  localTasks.value = [...newTasks]
}, { deep: true, immediate: true })

function onDragStart(index: number) {
  dragIndex.value = index
  isDragging.value = true
}

function onDrop(index: number) {
  if (dragIndex.value === -1 || dragIndex.value === index) return
  
  // Перемещаем элемент
  const [movedTask] = localTasks.value.splice(dragIndex.value, 1)
  localTasks.value.splice(index, 0, movedTask)
  
  // Вызываем onSortEnd для сохранения на сервере
  onSortEnd({ newIndex: index })
  dragIndex.value = -1
}

async function handleToggle(id: number) {
  const task = props.tasks.find(t => t.id === id)
  if (!task) return
  if (task.status === 'done') await tasksStore.updateTask(id, { status: 'todo' })
  else await tasksStore.completeTask(id)
  emit('task-toggle', id)
}

function handleClick(id: number) {
  emit('task-click', id)
}

async function handleDelete(id: number) {
  if (confirm('Удалить задачу?')) {
    await tasksStore.deleteTask(id)
    emit('task-delete', id)
  }
}

async function onSortEnd(event: any) {
  isDragging.value = false

  const movedTask = localTasks.value[event.newIndex]
  if (!movedTask) return

  const prevTask = event.newIndex > 0 ? localTasks.value[event.newIndex - 1] : null
  const nextTask = event.newIndex < localTasks.value.length - 1 ? localTasks.value[event.newIndex + 1] : null

  let newPosition: number
  if (!prevTask && !nextTask) {
    newPosition = 1000
  } else if (!prevTask) {
    newPosition = (nextTask?.position || 2000) / 2
  } else if (!nextTask) {
    newPosition = (prevTask?.position || 0) + 1000
  } else {
    newPosition = ((prevTask?.position || 0) + (nextTask?.position || 2000)) / 2
  }

  try {
    await tasksStore.reorderTask(movedTask.id, newPosition)
    showToast('Порядок задач обновлён', 'success')
    await tasksStore.fetchTasks(tasksStore.filters)
  } catch {
    showToast('Не удалось обновить порядок', 'error')
    localTasks.value = [...props.tasks]
  }
}
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

    <!-- Task list with drag handles -->
    <div v-else class="space-y-1">
      <div
        v-for="(task, index) in localTasks"
        :key="task.id"
        class="flex items-start gap-2 group/task rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
        :class="{ 'cursor-grabbing': isDragging }"
      >
        <!-- Drag handle -->
        <div
          class="drag-handle flex items-center justify-center w-6 h-6 mt-1 rounded cursor-grab hover:bg-gray-100 dark:hover:bg-gray-700 opacity-40 group-hover/task:opacity-100 transition-opacity flex-shrink-0"
          draggable="true"
          @dragstart="onDragStart(index)"
          @dragover.prevent
          @drop="onDrop(index)"
          title="Перетащить"
        >
          <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z" />
          </svg>
        </div>

        <!-- Task item -->
        <div class="flex-1 min-w-0">
          <TaskItem
            :task="task"
            :show-project="showProject"
            :compact="compact"
            @toggle="handleToggle"
            @click="handleClick"
            @delete="handleDelete"
          />
        </div>
      </div>

      <!-- Подсказка -->
      <div v-if="localTasks.length > 1" class="mt-2 text-xs text-gray-400 text-center">
        💡 Перетащите за иконку ⠿ для изменения порядка
      </div>
    </div>
  </div>
</template>

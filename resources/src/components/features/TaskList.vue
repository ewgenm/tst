<!-- TaskList с Drag & Drop для задач и подзадач -->
<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import type { Task } from '@/types'
import TaskItem from './TaskItem.vue'
import { useTasksStore } from '@/stores/tasks'
import { useToast } from '@/composables/useToast'

interface Props { tasks: Task[]; showProject?: boolean; compact?: boolean; emptyMessage?: string; isLoading?: boolean }
const props = withDefaults(defineProps<Props>(), { showProject: false, compact: false, emptyMessage: 'Нет задач', isLoading: false })

const emit = defineEmits<{
  'task-click': [task: Task]
  'task-toggle': [id: number]
  'task-delete': [id: number]
}>()

const tasksStore = useTasksStore()
const { show: showToast } = useToast()
const isDragging = ref(false)

// Фильтруем только корневые задачи (без parent_task_id)
const rootTasks = computed(() => props.tasks.filter(t => !t.parent_task_id))

// Локальная копия для drag-and-drop
const localTasks = ref<Task[]>([...rootTasks.value])

watch(() => props.tasks, (newTasks) => {
  localTasks.value = [...newTasks.filter(t => !t.parent_task_id)]
}, { deep: true, immediate: true })

async function handleToggle(id: number) {
  const task = props.tasks.find(t => t.id === id)
  if (!task) return
  if (task.status === 'done') await tasksStore.updateTask(id, { status: 'todo' })
  else await tasksStore.completeTask(id)
  emit('task-toggle', id)
}

function handleClick(task: Task) {
  emit('task-click', task)
}

async function handleDelete(id: number) {
  if (confirm('Удалить задачу?')) {
    await tasksStore.deleteTask(id)
    emit('task-delete', id)
  }
}

// Drag & Drop для корневых задач
const dragTaskId = ref<number | null>(null)
const dragOverTaskId = ref<number | null>(null)

function onDragStart(taskId: number) {
  dragTaskId.value = taskId
}

function onDragOver(taskId: number, event: DragEvent) {
  event.preventDefault()
  dragOverTaskId.value = taskId
}

function onDragLeave() {
  dragOverTaskId.value = null
}

async function onDrop(targetTaskId: number) {
  if (dragTaskId.value === null || dragTaskId.value === targetTaskId) return

  try {
    // Перемещаем задачу как подзадачу целевой задачи
    await tasksStore.moveTask(dragTaskId.value, targetTaskId)
    showToast('Задача перемещена как подзадача', 'success')
    await tasksStore.fetchTasks(tasksStore.filters)
  } catch {
    showToast('Не удалось переместить задачу', 'error')
  }

  dragTaskId.value = null
  dragOverTaskId.value = null
}

// Перемещение в корень (на пустое место)
async function onDropToRoot() {
  if (dragTaskId.value === null) return

  try {
    await tasksStore.moveTask(dragTaskId.value, null)
    showToast('Задача перемещена в корень', 'success')
    await tasksStore.fetchTasks(tasksStore.filters)
  } catch {
    showToast('Не удалось переместить задачу', 'error')
  }

  dragTaskId.value = null
  dragOverTaskId.value = null
}

// Drag & Drop для сортировки корневых задач
async function onSortEnd(event: any) {
  isDragging.value = false

  const movedTask = localTasks.value[event.newIndex]
  if (!movedTask) return

  // Только сортировка среди корневых задач
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
    localTasks.value = [...rootTasks.value]
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
    <div v-else-if="rootTasks.length === 0" class="text-center py-12 text-gray-500">
      <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
      </svg>
      <p class="text-lg font-medium">{{ emptyMessage }}</p>
    </div>

    <!-- Task list -->
    <div v-else class="space-y-1" @dragover.prevent @drop="onDropToRoot">
      <div
        v-for="task in localTasks"
        :key="task.id"
        class="flex items-start gap-2 group/task rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors relative"
        :class="{ 'bg-primary-50 dark:bg-primary-900/10': dragOverTaskId === task.id }"
        @dragover="onDragOver(task.id, $event)"
        @dragleave="onDragLeave"
        @drop="onDrop(task.id)"
      >
        <!-- Drag handle для сортировки -->
        <div
          class="drag-handle flex items-center justify-center w-6 h-6 mt-1 rounded cursor-grab hover:bg-gray-100 dark:hover:bg-gray-700 opacity-0 group-hover/task:opacity-100 transition-opacity flex-shrink-0"
          draggable="true"
          @dragstart="onDragStart(task.id)"
          title="Перетащить"
        >
          <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z" />
          </svg>
        </div>

        <!-- Task Item (с вложенными подзадачами) -->
        <div class="flex-1 min-w-0">
          <TaskItem
            :task="task"
            :show-project="showProject"
            :compact="compact"
            :is-draggable="false"
            @toggle="handleToggle"
            @click="handleClick"
            @delete="handleDelete"
          />
        </div>
      </div>

      <!-- Подсказка -->
      <div v-if="localTasks.length > 1" class="mt-2 text-xs text-gray-400 text-center">
        💡 Перетащите задачу на другую задачу чтобы сделать её подзадачей
      </div>
    </div>
  </div>
</template>

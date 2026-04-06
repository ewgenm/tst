<!-- ============================================================
TaskItem — с вложенными подзадачами, прогресс-баром и drag & drop
============================================================ -->

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import type { Task } from '@/types'
import { useDateFormatter } from '@/composables/useDateFormatter'
import { useTasksStore } from '@/stores/tasks'
import { useToast } from '@/composables/useToast'

interface Props {
  task: Task
  showProject?: boolean
  compact?: boolean
  level?: number // Уровень вложенности (0 = корень)
  isDraggable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showProject: false,
  compact: false,
  level: 0,
  isDraggable: true,
})

const emit = defineEmits<{
  toggle: [id: number]
  edit: [id: number]
  delete: [id: number]
  click: [id: number]
  move: [taskId: number, newParentId: number | null]
}>()

const tasksStore = useTasksStore()
const { show: showToast } = useToast()
const { formatDueRelative, isOverdue } = useDateFormatter()

// Состояние
const isExpanded = ref(false)
const subtasks = ref<Task[]>([])
const isLoadingSubtasks = ref(false)

const priorityColors = {
  low: 'bg-gray-400',
  medium: 'bg-blue-500',
  high: 'bg-amber-500',
  urgent: 'bg-red-500',
}

const statusColors = {
  todo: 'text-gray-900 dark:text-gray-100',
  in_progress: 'text-amber-700 dark:text-amber-400',
  review: 'text-purple-700 dark:text-purple-400',
  done: 'text-green-700 dark:text-green-400 line-through opacity-75',
}

// Вычисляемые свойства
const progressPercent = ref(0)

const subtasksTotal = computed(() => props.task.subtasks_total ?? (props.task as any).subtasks_count ?? 0)
const subtasksCompleted = computed(() => props.task.subtasks_completed ?? (props.task as any).subtasks_completed_count ?? 0)

watch(
  () => [subtasksTotal.value, subtasksCompleted.value],
  () => {
    progressPercent.value = subtasksTotal.value > 0
      ? Math.round((subtasksCompleted.value / subtasksTotal.value) * 100)
      : 0
  },
  { immediate: true }
)

// Загрузка подзадач при разворачивании
async function toggleExpand() {
  isExpanded.value = !isExpanded.value
  if (isExpanded.value && subtasks.value.length === 0 && !isLoadingSubtasks.value) {
    await loadSubtasks()
  }
}

async function loadSubtasks() {
  isLoadingSubtasks.value = true
  try {
    subtasks.value = await tasksStore.fetchSubtasksRecursive(props.task.id)
  } finally {
    isLoadingSubtasks.value = false
  }
}

async function handleToggle(id: number) {
  emit('toggle', id)
}

function handleClick(id: number) {
  emit('click', id)
}

async function handleDelete(id: number) {
  if (confirm('Удалить задачу?')) {
    emit('delete', id)
  }
}

// Drag & Drop для подзадач
const dragTaskId = ref<number | null>(null)

function onDragStart(taskId: number) {
  dragTaskId.value = taskId
}

function onDragOver(event: DragEvent) {
  event.preventDefault()
}

async function onDrop(targetTaskId: number) {
  if (dragTaskId.value === null || dragTaskId.value === targetTaskId) return

  const draggedTask = subtasks.value.find(t => t.id === dragTaskId.value) ||
    tasksStore.tasks.find(t => t.id === dragTaskId.value)

  if (!draggedTask) return

  try {
    await tasksStore.moveTask(dragTaskId.value, targetTaskId)
    showToast('Задача перемещена', 'success')

    // Обновляем список подзадач
    if (isExpanded.value) {
      await loadSubtasks()
    }
    await tasksStore.fetchTasks(tasksStore.filters)
  } catch {
    showToast('Не удалось переместить задачу', 'error')
  }

  dragTaskId.value = null
}

// Перемещение подзадачи в корень (на пустое место)
async function onDropToRoot() {
  if (dragTaskId.value === null) return

  try {
    await tasksStore.moveTask(dragTaskId.value, null)
    showToast('Задача перемещена в корень', 'success')
    await tasksStore.fetchTasks(tasksStore.filters)
    if (isExpanded.value) {
      await loadSubtasks()
    }
  } catch {
    showToast('Не удалось переместить задачу', 'error')
  }

  dragTaskId.value = null
}
</script>

<template>
  <div class="task-item" :data-task-id="task.id">
    <!-- Основная задача -->
    <div
      class="flex items-start gap-2 group/task rounded-lg transition-colors"
      :class="[
        level > 0 ? 'ml-' + (level * 4) : '',
        { 'hover:bg-gray-50 dark:hover:bg-gray-800/50': isDraggable }
      ]"
      :data-level="level"
      @dragover="onDragOver"
      @drop="onDrop(task.id)"
    >
      <!-- Drag handle -->
      <div
        v-if="isDraggable"
        class="drag-handle flex items-center justify-center w-6 h-6 mt-1 rounded cursor-grab hover:bg-gray-100 dark:hover:bg-gray-700 opacity-0 group-hover/task:opacity-100 transition-opacity flex-shrink-0"
        draggable="true"
        @dragstart="onDragStart(task.id)"
        title="Перетащить"
      >
        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
          <path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z" />
        </svg>
      </div>

      <!-- Кнопка разворачивания -->
      <button
        v-if="subtasksTotal > 0"
        @click="toggleExpand"
        class="w-5 h-5 mt-1 flex items-center justify-center rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex-shrink-0"
        :title="isExpanded ? 'Свернуть' : 'Развернуть'"
      >
        <span class="text-gray-500 text-sm font-bold">{{ isExpanded ? '−' : '+' }}</span>
      </button>
      <div v-else class="w-5 h-5 mt-1 flex-shrink-0"></div>

      <!-- Чекбокс статуса -->
      <button
        @click="handleToggle(task.id)"
        class="mt-0.5 w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-colors"
        :class="task.status === 'done' ? 'bg-green-500 border-green-500' : 'border-gray-300 dark:border-gray-600 hover:border-primary-500'"
      >
        <svg v-if="task.status === 'done'" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
        </svg>
      </button>

      <!-- Контент задачи -->
      <div class="flex-1 min-w-0 cursor-pointer" @click="handleClick(task.id)">
        <div class="flex items-center gap-2">
          <span class="text-sm font-medium truncate" :class="statusColors[task.status]">
            {{ task.title }}
          </span>
          <span v-if="task.priority !== 'medium'" class="w-2 h-2 rounded-full flex-shrink-0" :class="priorityColors[task.priority]" />
        </div>

        <!-- Прогресс-бар подзадач -->
        <div v-if="subtasksTotal > 0" class="mt-1 flex items-center gap-2">
          <div class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
            <div
              class="h-full bg-green-500 rounded-full transition-all"
              :style="{ width: progressPercent + '%' }"
            />
          </div>
          <span class="text-xs text-gray-500 flex-shrink-0">
            {{ subtasksCompleted }}/{{ subtasksTotal }}
          </span>
        </div>

        <!-- Мета-информация -->
        <div v-if="!compact" class="flex items-center gap-3 mt-1 text-xs text-gray-500">
          <span v-if="showProject && task.project" class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700">
            {{ task.project.name }}
          </span>
          <span v-if="task.due_at" class="flex items-center gap-1" :class="{ 'text-red-600 dark:text-red-400 font-medium': isOverdue(task.due_at) && task.status !== 'done' }">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            {{ formatDueRelative(task.due_at) }}
          </span>
          <span v-if="task.is_recurring" title="Повторяющаяся задача">🔁</span>
          <span v-if="task.comments_count && task.comments_count > 0">💬 {{ task.comments_count }}</span>
          <span v-if="task.attachments_count && task.attachments_count > 0">📎 {{ task.attachments_count }}</span>
        </div>
      </div>

      <!-- Действия -->
      <div class="opacity-0 group-hover/task:opacity-100 transition-opacity flex items-center gap-1 flex-shrink-0">
        <button @click.stop="handleClick(task.id)" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors" title="Редактировать">
          <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
          </svg>
        </button>
        <button @click.stop="handleDelete(task.id)" class="p-1.5 hover:bg-red-100 dark:hover:bg-red-900 rounded transition-colors text-red-600" title="Удалить">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Подзадачи -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 max-h-0"
      enter-to-class="opacity-100 max-h-[2000px]"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="opacity-100 max-h-[2000px]"
      leave-to-class="opacity-0 max-h-0"
    >
      <div v-if="isExpanded" class="mt-1 space-y-1 overflow-hidden" @dragover="onDragOver" @drop="onDropToRoot">
        <!-- Loading -->
        <div v-if="isLoadingSubtasks" class="ml-11 space-y-1">
          <div v-for="i in 2" :key="i" class="flex items-center gap-2 p-2 animate-pulse">
            <div class="w-4 h-4 rounded-full bg-gray-200 dark:bg-gray-700"></div>
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
          </div>
        </div>

        <!-- Подзадачи -->
        <TaskItem
          v-for="subtask in subtasks.filter(s => !s.parent_task_id || s.parent_task_id === task.id)"
          :key="subtask.id"
          :task="subtask"
          :show-project="showProject"
          :compact="compact"
          :level="level + 1"
          :is-draggable="true"
          @toggle="handleToggle"
          @click="handleClick"
          @delete="handleDelete"
          @move="$emit('move', $event)"
        />
      </div>
    </Transition>
  </div>
</template>

<!-- TaskList с Drag & Drop (CRITICAL FIX #8) -->
<script setup lang="ts">
import { ref } from 'vue'
import draggable from 'vuedraggable'
import type { Task } from '@/types'
import TaskItem from './TaskItem.vue'
import { useTasksStore } from '@/stores/tasks'
import { useToast } from '@/composables/useToast'

interface Props { tasks: Task[]; showProject?: boolean; compact?: boolean; emptyMessage?: string; isLoading?: boolean }
const props = withDefaults(defineProps<Props>(), { showProject: false, compact: false, emptyMessage: 'Нет задач', isLoading: false })

const tasksStore = useTasksStore()
const { show: showToast } = useToast()

async function handleToggle(id: number) {
  const task = props.tasks.find(t => t.id === id)
  if (!task) return
  if (task.status === 'done') await tasksStore.updateTask(id, { status: 'todo' })
  else await tasksStore.completeTask(id)
}

async function onSortEnd(event: any) {
  const taskId = props.tasks[event.newIndex]?.id
  if (!taskId) return
  try {
    await tasksStore.reorderTask(taskId, event.newIndex * 1000)
    showToast('Порядок обновлён', 'success')
  } catch { showToast('Не удалось обновить порядок', 'error') }
}

function handleEdit(id: number) { /* navigate */ }
async function handleDelete(id: number) { if (confirm('Удалить задачу?')) await tasksStore.deleteTask(id) }
</script>

<template>
  <div>
    <div v-if="isLoading" class="space-y-2">
      <div v-for="i in 5" :key="i" class="flex items-start gap-3 p-3 rounded-lg animate-pulse">
        <div class="w-5 h-5 rounded-full bg-gray-200 dark:bg-gray-700 flex-shrink-0"></div>
        <div class="flex-1 space-y-2"><div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div><div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div></div>
      </div>
    </div>
    <div v-else-if="tasks.length === 0" class="text-center py-12 text-gray-500">
      <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
      <p class="text-lg font-medium">{{ emptyMessage }}</p>
    </div>
    <draggable v-else v-model="tasksStore.tasks" item-key="id" :animation="200" @end="onSortEnd">
      <template #item="{ element }">
        <TaskItem :task="element" :show-project="showProject" :compact="compact" @toggle="handleToggle" @edit="handleEdit" @delete="handleDelete" />
      </template>
    </draggable>
  </div>
</template>

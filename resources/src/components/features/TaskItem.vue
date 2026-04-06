<!-- ============================================================
TaskItem компонент (с timezone support) — ТЗ №2 v1.1 раздел 5.2
============================================================ -->

<script setup lang="ts">
import type { Task } from '@/types'
import { useDateFormatter } from '@/composables/useDateFormatter'

interface Props { task: Task; showProject?: boolean; compact?: boolean }
const props = withDefaults(defineProps<Props>(), { showProject: false, compact: false })
const emit = defineEmits<{ toggle: [id: number]; edit: [id: number]; delete: [id: number]; click: [id: number] }>()

const { formatDueRelative, isOverdue } = useDateFormatter()

const priorityColors = { low: 'bg-gray-400', medium: 'bg-blue-500', high: 'bg-amber-500', urgent: 'bg-red-500' }
const statusColors = { todo: 'text-gray-900 dark:text-gray-100', in_progress: 'text-amber-700 dark:text-amber-400', review: 'text-purple-700 dark:text-purple-400', done: 'text-green-700 dark:text-green-400 line-through opacity-75' }
</script>

<template>
  <div class="group flex items-start gap-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors cursor-pointer" :class="{ 'opacity-60': task.status === 'done' }" @click="emit('click', task.id); emit('edit', task.id)">
    <button @click.stop="emit('toggle', task.id)" class="mt-0.5 w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-colors" :class="task.status === 'done' ? 'bg-green-500 border-green-500' : 'border-gray-300 dark:border-gray-600 hover:border-primary-500'">
      <svg v-if="task.status === 'done'" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
    </button>
    <div class="flex-1 min-w-0">
      <div class="flex items-center gap-2">
        <span class="text-sm font-medium truncate" :class="statusColors[task.status]">{{ task.title }}</span>
        <span v-if="task.priority !== 'medium'" class="w-2 h-2 rounded-full flex-shrink-0" :class="priorityColors[task.priority]" />
        <span v-if="task.subtasks_total && task.subtasks_total > 0" class="text-xs text-gray-500 flex-shrink-0">{{ task.subtasks_completed }}/{{ task.subtasks_total }}</span>
      </div>
      <div v-if="!compact" class="flex items-center gap-3 mt-1 text-xs text-gray-500">
        <span v-if="showProject && task.project" class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700">{{ task.project.name }}</span>
        <span v-if="task.due_at" class="flex items-center gap-1" :class="{ 'text-red-600 dark:text-red-400 font-medium': isOverdue(task.due_at) && task.status !== 'done' }">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
          {{ formatDueRelative(task.due_at) }}
        </span>
        <span v-if="task.is_recurring" title="Повторяющаяся задача">🔁</span>
        <span v-if="task.comments_count && task.comments_count > 0">💬 {{ task.comments_count }}</span>
        <span v-if="task.attachments_count && task.attachments_count > 0">📎 {{ task.attachments_count }}</span>
      </div>
    </div>
    <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1 flex-shrink-0">
      <button @click.stop="emit('edit', task.id)" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors" title="Редактировать"><svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg></button>
      <button @click.stop="emit('delete', task.id)" class="p-1.5 hover:bg-red-100 dark:hover:bg-red-900 rounded transition-colors text-red-600" title="Удалить"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
    </div>
  </div>
</template>

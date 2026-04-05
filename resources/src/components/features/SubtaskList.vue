<!-- SubtaskList компонент -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import type { Task } from '@/types'
import { useTasksStore } from '@/stores/tasks'
import { useToast } from '@/composables/useToast'

interface Props { parentTaskId: number; subtasks?: Task[] }
const props = defineProps<Props>()
const tasksStore = useTasksStore()
const { show: showToast } = useToast()
const newSubtaskTitle = ref('')
const isAdding = ref(false)

const subtasksList = computed(() => props.subtasks || [])
const completedCount = computed(() => subtasksList.value.filter(t => t.status === 'done').length)
const totalCount = computed(() => subtasksList.value.length)
const progressPercent = computed(() => totalCount.value > 0 ? Math.round((completedCount.value / totalCount.value) * 100) : 0)

async function addSubtask() { if (!newSubtaskTitle.value.trim()) return; await tasksStore.createTask({ title: newSubtaskTitle.value, parent_task_id: props.parentTaskId, status: 'todo', priority: 'medium' }); newSubtaskTitle.value = ''; isAdding.value = false }
async function toggleSubtask(subtask: Task) { const newStatus = subtask.status === 'done' ? 'todo' : 'done'; if (newStatus === 'done') await tasksStore.completeTask(subtask.id); else await tasksStore.updateTask(subtask.id, { status: 'todo' }) }
async function deleteSubtask(subtaskId: number) { if (!confirm('Удалить подзадачу?')) return; await tasksStore.deleteTask(subtaskId) }
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold flex items-center gap-2">📋 Подзадачи <span class="text-sm font-normal text-gray-500">({{ completedCount }}/{{ totalCount }})</span></h3>
      <button @click="isAdding = !isAdding" class="text-sm text-primary-600 hover:underline">{{ isAdding ? 'Отменить' : '+ Добавить' }}</button>
    </div>
    <div v-if="totalCount > 0" class="mb-4 bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden"><div class="h-full bg-green-500 transition-all duration-300" :style="{ width: progressPercent + '%' }" /></div>
    <div v-if="isAdding" class="mb-3 flex gap-2"><input v-model="newSubtaskTitle" type="text" placeholder="Название подзадачи..." class="flex-1 px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 text-sm" @keyup.enter="addSubtask" /><button @click="addSubtask" :disabled="!newSubtaskTitle.trim()" class="px-4 py-2 bg-primary-600 text-white rounded-lg disabled:opacity-50 text-sm">Добавить</button></div>
    <div v-if="subtasksList.length === 0 && !isAdding" class="text-center py-4 text-gray-500 text-sm">Нет подзадач</div>
    <div v-else class="space-y-2">
      <div v-for="subtask in subtasksList" :key="subtask.id" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 group">
        <button @click="toggleSubtask(subtask)" class="w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0" :class="subtask.status === 'done' ? 'bg-green-500 border-green-500' : 'border-gray-300 dark:border-gray-600'"><svg v-if="subtask.status === 'done'" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg></button>
        <span class="flex-1 text-sm" :class="{ 'line-through text-gray-500': subtask.status === 'done' }">{{ subtask.title }}</span>
        <button @click="deleteSubtask(subtask.id)" class="opacity-0 group-hover:opacity-100 p-1 hover:bg-red-100 dark:hover:bg-red-900/20 rounded text-red-600 transition-all" title="Удалить"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
      </div>
    </div>
  </div>
</template>

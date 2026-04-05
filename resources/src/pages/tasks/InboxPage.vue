<!-- InboxPage -->
<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useTasksStore } from '@/stores/tasks'
import TaskList from '@/components/features/TaskList.vue'
import TaskForm from '@/components/features/TaskForm.vue'

const tasksStore = useTasksStore()
const showTaskForm = ref(false)
const inboxTasks = computed(() => tasksStore.inboxTasks)

onMounted(async () => { await tasksStore.fetchTasks({ project_id: null, status: 'todo,in_progress,review' }) })
function handleTaskCreated() { showTaskForm.value = false }
</script>

<template>
  <div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <div><h1 class="text-2xl font-bold">📥 Входящие</h1><p class="text-sm text-gray-500 mt-1">Задачи без проекта ({{ inboxTasks.length }})</p></div>
      <button @click="showTaskForm = !showTaskForm" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>Новая задача</button>
    </div>
    <div v-if="showTaskForm" class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-200 dark:border-gray-700"><h2 class="text-lg font-semibold mb-4">Создать задачу</h2><TaskForm @created="handleTaskCreated" @cancel="showTaskForm = false" /></div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4"><TaskList :tasks="inboxTasks" :is-loading="tasksStore.isLoading" empty-message="Входящие пусты 🎉" /></div>
  </div>
</template>

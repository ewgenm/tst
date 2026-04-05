<!-- SearchPage (CRITICAL FIX #1) -->
<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSearchStore } from '@/stores/search'
import { useTasksStore } from '@/stores/tasks'
import TaskItem from '@/components/features/TaskItem.vue'

const route = useRoute()
const router = useRouter()
const searchStore = useSearchStore()
const tasksStore = useTasksStore()

onMounted(() => { const query = route.query.q as string; if (query) searchStore.search(query) })
const hasResults = computed(() => searchStore.results && (searchStore.results.tasks?.length > 0 || searchStore.results.projects?.length > 0 || searchStore.results.habits?.length > 0))
function handleTaskEdit(id: number) { router.push(`/tasks/${id}`) }
function handleTaskToggle(id: number) { tasksStore.completeTask(id) }
function navigateToProject(id: number) { router.push(`/projects/${id}`) }
</script>

<template>
  <div class="max-w-6xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">🔍 Поиск: "{{ route.query.q }}"</h1>
    <div v-if="searchStore.isLoading" class="text-center py-12"><div class="w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full animate-spin mx-auto"></div></div>
    <div v-else-if="!hasResults" class="text-center py-12 text-gray-500"><svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg><p class="text-lg font-medium">Ничего не найдено</p></div>
    <div v-else class="space-y-8">
      <section v-if="searchStore.results?.tasks?.length"><h2 class="text-lg font-semibold mb-3">Задачи ({{ searchStore.results.tasks.length }})</h2><div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4"><TaskItem v-for="task in searchStore.results.tasks" :key="task.id" :task="task" :show-project="true" @edit="handleTaskEdit" @toggle="handleTaskToggle" /></div></section>
      <section v-if="searchStore.results?.projects?.length"><h2 class="text-lg font-semibold mb-3">Проекты ({{ searchStore.results.projects.length }})</h2><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"><div v-for="project in searchStore.results.projects" :key="project.id" class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg transition-shadow cursor-pointer" @click="navigateToProject(project.id)"><div class="flex items-start gap-3"><div class="w-10 h-10 rounded-lg flex-shrink-0 flex items-center justify-center text-white" :style="{ backgroundColor: project.color }">{{ project.icon || '📁' }}</div><div class="flex-1 min-w-0"><h3 class="font-semibold truncate">{{ project.name }}</h3><p v-if="project.description" class="text-sm text-gray-500 truncate mt-1">{{ project.description }}</p><div class="flex items-center gap-2 mt-2 text-xs text-gray-500"><span v-if="project.tasks_count">{{ project.tasks_count }} задач</span><span v-if="project.is_favorite">⭐</span></div></div></div></div></div></section>
      <section v-if="searchStore.results?.habits?.length"><h2 class="text-lg font-semibold mb-3">Привычки ({{ searchStore.results.habits.length }})</h2><div class="space-y-3"><div v-for="habit in searchStore.results.habits" :key="habit.id" class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4"><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-lg flex items-center justify-center text-white" :style="{ backgroundColor: habit.color }">{{ habit.icon || '✨' }}</div><div><h3 class="font-semibold">{{ habit.name }}</h3><p class="text-sm text-gray-500">🔥 {{ habit.current_streak }} дн.</p></div></div></div></div></section>
    </div>
  </div>
</template>

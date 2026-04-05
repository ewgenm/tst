<!-- ProjectsPage -->
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useProjectsStore } from '@/stores/projects'
import ProjectCard from '@/components/features/ProjectCard.vue'

const projectsStore = useProjectsStore()
const showProjectForm = ref(false)
const activeTab = ref<'active' | 'favorites' | 'archived'>('active')
const newProjectName = ref('')
const newProjectDescription = ref('')
const newProjectColor = ref('#3B82F6')
const newProjectIcon = ref('📁')
const quickIcons = ['📁', '💼', '🎯', '🚀', '💡', '📚', '🎨', '⚡', '🔥', '🏆']

onMounted(async () => { await projectsStore.fetchProjects() })

const displayedProjects = computed(() => {
  if (activeTab.value === 'favorites') return projectsStore.favoriteProjects
  if (activeTab.value === 'archived') return projectsStore.archivedProjects
  return projectsStore.activeProjects
})
const tabCounts = computed(() => ({ active: projectsStore.activeProjects.length, favorites: projectsStore.favoriteProjects.length, archived: projectsStore.archivedProjects.length }))

async function createProject() { if (!newProjectName.value.trim()) return; await projectsStore.createProject({ name: newProjectName.value, description: newProjectDescription.value || null, color: newProjectColor.value, icon: newProjectIcon.value }); showProjectForm.value = false; newProjectName.value = ''; newProjectDescription.value = '' }
</script>

<template>
  <div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <div><h1 class="text-2xl font-bold">📁 Проекты</h1><p class="text-sm text-gray-500 mt-1">Управление проектами</p></div>
      <button @click="showProjectForm = !showProjectForm" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>Новый проект</button>
    </div>
    <div v-if="showProjectForm" class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-200 dark:border-gray-700">
      <h2 class="text-lg font-semibold mb-4">Создать проект</h2>
      <form @submit.prevent="createProject" class="space-y-4">
        <div><label class="block text-sm font-medium mb-1">Название *</label><input v-model="newProjectName" type="text" required maxlength="100" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" /></div>
        <div><label class="block text-sm font-medium mb-1">Описание</label><textarea v-model="newProjectDescription" rows="2" maxlength="1000" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" /></div>
        <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium mb-1">Цвет</label><input v-model="newProjectColor" type="color" class="w-full h-10 rounded-lg border border-gray-300 dark:border-gray-600 cursor-pointer" /></div><div><label class="block text-sm font-medium mb-1">Иконка</label><div class="flex gap-2 flex-wrap"><button v-for="icon in quickIcons" :key="icon" type="button" @click="newProjectIcon = icon" class="w-10 h-10 rounded-lg text-lg flex items-center justify-center" :class="newProjectIcon === icon ? 'bg-primary-100 dark:bg-primary-900/30 border-2 border-primary-500' : 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200'">{{ icon }}</button></div></div></div>
        <div class="flex gap-2"><button type="submit" :disabled="projectsStore.isLoading" class="flex-1 bg-primary-600 text-white py-2 rounded-lg disabled:opacity-50">Создать</button><button type="button" @click="showProjectForm = false" class="px-4 py-2 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Отмена</button></div>
      </form>
    </div>
    <div class="flex gap-2 mb-6 border-b border-gray-200 dark:border-gray-700">
      <button @click="activeTab = 'active'" class="px-4 py-2 border-b-2 font-medium" :class="activeTab === 'active' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500'">Активные ({{ tabCounts.active }})</button>
      <button @click="activeTab = 'favorites'" class="px-4 py-2 border-b-2 font-medium" :class="activeTab === 'favorites' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500'">⭐ Избранные ({{ tabCounts.favorites }})</button>
      <button @click="activeTab = 'archived'" class="px-4 py-2 border-b-2 font-medium" :class="activeTab === 'archived' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500'">📦 Архив ({{ tabCounts.archived }})</button>
    </div>
    <div v-if="projectsStore.isLoading" class="text-center py-12"><div class="w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full animate-spin mx-auto"></div></div>
    <div v-else-if="displayedProjects.length === 0" class="text-center py-12 text-gray-500"><svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg><p class="text-lg font-medium">{{ activeTab === 'active' ? 'Нет активных проектов' : activeTab === 'favorites' ? 'Нет избранных проектов' : 'Архив пуст' }}</p></div>
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"><ProjectCard v-for="project in displayedProjects" :key="project.id" :project="project" /></div>
  </div>
</template>

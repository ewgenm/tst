<!-- ProjectCard компонент -->
<script setup lang="ts">
import type { Project } from '@/types'
import { useProjectsStore } from '@/stores/projects'
import { useRouter } from 'vue-router'

interface Props { project: Project }
const props = defineProps<Props>()
const router = useRouter()
const projectsStore = useProjectsStore()

function navigateToProject() { router.push(`/projects/${props.project.id}`) }
async function toggleFavorite(event: Event) { event.stopPropagation(); await projectsStore.toggleFavorite(props.project.id) }
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-5 hover:shadow-lg transition-all cursor-pointer group" @click="navigateToProject">
    <div class="flex items-start justify-between mb-3">
      <div class="w-12 h-12 rounded-lg flex items-center justify-center text-white text-xl" :style="{ backgroundColor: project.color }">{{ project.icon || '📁' }}</div>
      <button @click="toggleFavorite" class="text-xl opacity-0 group-hover:opacity-100 transition-opacity">{{ project.is_favorite ? '⭐' : '☆' }}</button>
    </div>
    <h3 class="font-semibold text-lg truncate mb-1">{{ project.name }}</h3>
    <p v-if="project.description" class="text-sm text-gray-500 line-clamp-2 mb-3">{{ project.description }}</p>
    <div class="flex items-center gap-3 text-xs text-gray-500 pt-3 border-t border-gray-100 dark:border-gray-700">
      <span v-if="project.tasks_count !== undefined">📋 {{ project.tasks_count }}</span>
      <span v-if="project.is_archived" class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">📦 Архив</span>
      <span class="ml-auto">{{ new Date(project.updated_at).toLocaleDateString('ru-RU') }}</span>
    </div>
  </div>
</template>

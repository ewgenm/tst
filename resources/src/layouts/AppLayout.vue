<!-- AppLayout.vue -->
<script setup lang="ts">
import { watch } from 'vue'
import { useRoute } from 'vue-router'
import Sidebar from '@/components/layout/Sidebar.vue'
import Header from '@/components/layout/Header.vue'
import { useProjectsStore } from '@/stores/projects'

const route = useRoute()
const projectsStore = useProjectsStore()

watch(() => route.params.id, (newProjectId) => {
  if (route.path.startsWith('/projects/') && newProjectId) projectsStore.setupProjectRealtime(Number(newProjectId))
}, { immediate: true })
</script>

<template>
  <div class="flex h-screen bg-gray-50 dark:bg-gray-900">
    <Sidebar />
    <div class="flex-1 flex flex-col overflow-hidden">
      <Header />
      <main class="flex-1 overflow-y-auto p-6"><router-view /></main>
    </div>
  </div>
</template>

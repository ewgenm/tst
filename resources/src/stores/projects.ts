// ============================================================
// Projects Store (ТЗ №2 v1.1 — раздел 3.3)
// CRUD + Members + Invites + Archive
// ============================================================

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Project, ProjectMember, WsEventProjectMemberAdded } from '@/types'
import { apiClient } from '@/api/client'
import { endpoints } from '@/api/endpoints'
import { useUIStore } from '@/stores/ui'
import { useWebSocket } from '@/composables/useWebSocket'

export const useProjectsStore = defineStore('projects', () => {
  const projects = ref<Project[]>([])
  const currentProject = ref<Project | null>(null)
  const members = ref<ProjectMember[]>([])
  const isLoading = ref(false)

  const activeProjects = computed(() =>
    projects.value.filter(p => !p.is_archived).sort((a, b) => a.sort_order - b.sort_order)
  )

  const archivedProjects = computed(() => projects.value.filter(p => p.is_archived))
  const favoriteProjects = computed(() => projects.value.filter(p => p.is_favorite))

  async function fetchProjects(archived = false) {
    isLoading.value = true
    try {
      const response = await apiClient.get(endpoints.projects, {
        params: { archived, sort: 'sort_order', order: 'asc' }
      })
      projects.value = response.data.data
    } finally {
      isLoading.value = false
    }
  }

  async function fetchProject(id: number, include: string[] = []) {
    isLoading.value = true
    try {
      const response = await apiClient.get(endpoints.project(id), {
        params: { include: include.join(',') }
      })
      currentProject.value = response.data.data
      return currentProject.value
    } finally {
      isLoading.value = false
    }
  }

  async function createProject(payload: Partial<Project>) {
    const tempId = Date.now()
    const tempProject: Project = {
      ...payload,
      id: tempId,
      sort_order: projects.value.length,
      is_archived: false,
      is_favorite: false,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
      deleted_at: null,
    } as Project

    projects.value.push(tempProject)

    try {
      const response = await apiClient.post(endpoints.projects, payload)
      const index = projects.value.findIndex(p => p.id === tempId)
      if (index !== -1) projects.value[index] = response.data.data
      useUIStore().showToast('Проект создан', 'success')
      return response.data.data
    } catch (error: any) {
      projects.value = projects.value.filter(p => p.id !== tempId)
      useUIStore().showToast('Не удалось создать проект', 'error')
      throw error
    }
  }

  async function updateProject(id: number, payload: Partial<Project>) {
    const originalIndex = projects.value.findIndex(p => p.id === id)
    const originalProject = originalIndex !== -1 ? { ...projects.value[originalIndex] } : null

    if (originalIndex !== -1) {
      projects.value[originalIndex] = { ...projects.value[originalIndex], ...payload }
    }

    try {
      const response = await apiClient.put(endpoints.project(id), payload)
      projects.value[originalIndex] = response.data.data
      if (currentProject.value?.id === id) currentProject.value = response.data.data
      useUIStore().showToast('Проект обновлён', 'success')
      return response.data.data
    } catch (error: any) {
      if (originalIndex !== -1 && originalProject) projects.value[originalIndex] = originalProject
      useUIStore().showToast('Не удалось обновить проект', 'error')
      throw error
    }
  }

  async function deleteProject(id: number) {
    const originalIndex = projects.value.findIndex(p => p.id === id)
    const originalProject = originalIndex !== -1 ? projects.value[originalIndex] : null
    projects.value = projects.value.filter(p => p.id !== id)

    try {
      await apiClient.delete(endpoints.project(id))
      if (currentProject.value?.id === id) currentProject.value = null
      useUIStore().showToast('Проект удалён', 'success')
    } catch (error: any) {
      if (originalIndex !== -1 && originalProject) projects.value.splice(originalIndex, 0, originalProject)
      useUIStore().showToast('Не удалось удалить проект', 'error')
      throw error
    }
  }

  async function archiveProject(id: number) {
    try {
      await apiClient.post(endpoints.projectArchive(id))
      const project = projects.value.find(p => p.id === id)
      if (project) project.is_archived = true
      useUIStore().showToast('Проект архивирован', 'success')
    } catch (error: any) {
      useUIStore().showToast('Не удалось архивировать проект', 'error')
      throw error
    }
  }

  async function restoreProject(id: number) {
    try {
      await apiClient.post(endpoints.projectRestore(id))
      const project = projects.value.find(p => p.id === id)
      if (project) { project.is_archived = false; project.deleted_at = null }
      useUIStore().showToast('Проект восстановлен', 'success')
    } catch (error: any) {
      useUIStore().showToast('Не удалось восстановить проект', 'error')
      throw error
    }
  }

  async function toggleFavorite(id: number) {
    const project = projects.value.find(p => p.id === id)
    const originalValue = project?.is_favorite
    if (project) project.is_favorite = !project.is_favorite

    try {
      await apiClient.put(endpoints.project(id), { is_favorite: !originalValue })
    } catch (error: any) {
      if (project) project.is_favorite = originalValue ?? false
      useUIStore().showToast('Не удалось обновить избранное', 'error')
      throw error
    }
  }

  async function fetchMembers(projectId: number) {
    isLoading.value = true
    try {
      const response = await apiClient.get(endpoints.projectMembers(projectId))
      members.value = response.data.data
      return members.value
    } finally {
      isLoading.value = false
    }
  }

  async function inviteMember(projectId: number, email: string, role: 'admin' | 'member' | 'viewer') {
    const response = await apiClient.post(endpoints.projectInvite(projectId), { email, role })
    members.value.push(response.data.data)
    useUIStore().showToast('Приглашение отправлено', 'success')
    return response.data.data
  }

  async function acceptInvite(memberId: number) {
    const response = await apiClient.patch(endpoints.projectMemberAccept(memberId))
    const member = members.value.find(m => m.id === memberId)
    if (member) member.status = 'active'
    useUIStore().showToast('Приглашение принято', 'success')
    return response.data.data
  }

  async function removeMember(_projectId: number, memberId: number) {
    const originalIndex = members.value.findIndex(m => m.id === memberId)
    const originalMember = originalIndex !== -1 ? members.value[originalIndex] : null
    members.value = members.value.filter(m => m.id !== memberId)

    try {
      await apiClient.delete(endpoints.projectMemberDelete(memberId))
      useUIStore().showToast('Участник удалён', 'success')
    } catch (error: any) {
      if (originalIndex !== -1 && originalMember) members.value.splice(originalIndex, 0, originalMember)
      useUIStore().showToast('Не удалось удалить участника', 'error')
      throw error
    }
  }

  async function leaveProject(projectId: number) {
    try {
      await apiClient.delete(endpoints.projectLeave(projectId))
      members.value = members.value.filter(m => m.project_id !== projectId)
      useUIStore().showToast('Вы покинули проект', 'success')
    } catch (error: any) {
      useUIStore().showToast('Не удалось покинуть проект', 'error')
      throw error
    }
  }

  function setupProjectRealtime(projectId: number) {
    const { subscribeToProjectChannel } = useWebSocket()
    subscribeToProjectChannel(projectId, 'project.member.added', (payload: WsEventProjectMemberAdded) => {
      if (!members.value.find(m => m.user_id === payload.user.id)) {
        members.value.push({
          id: Date.now(), project_id: projectId, user_id: payload.user.id,
          role: payload.role as any, status: 'active', user: payload.user,
          invited_by: null, created_at: new Date().toISOString(), updated_at: new Date().toISOString(),
        })
        useUIStore().showToast(`${payload.user.name} присоединился к проекту`, 'info')
      }
    })
  }

  return {
    projects, currentProject, members, isLoading,
    activeProjects, archivedProjects, favoriteProjects,
    fetchProjects, fetchProject, createProject, updateProject, deleteProject,
    archiveProject, restoreProject, toggleFavorite,
    fetchMembers, inviteMember, acceptInvite, removeMember, leaveProject,
    setupProjectRealtime,
  }
})

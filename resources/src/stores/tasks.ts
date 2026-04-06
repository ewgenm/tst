// ============================================================
// Tasks Store (ТЗ №2 v1.1 — раздел 3.2)
// Optimistic updates с откатами (CRITICAL FIX #7)
// ============================================================

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Task, TaskFilters } from '@/types'
import { apiClient } from '@/api/client'
import { endpoints } from '@/api/endpoints'
import { useUIStore } from '@/stores/ui'
import { useWebSocket } from '@/composables/useWebSocket'

export const useTasksStore = defineStore('tasks', () => {
  const tasks = ref<Task[]>([])
  const isLoading = ref(false)
  const pagination = ref({
    current_page: 1,
    per_page: 20,
    total: 0,
    total_pages: 0,
    has_more: false
  })
  const filters = ref<TaskFilters>({})
  const uiStore = useUIStore()

  // ============================================================
  // Computed
  // ============================================================
  const inboxTasks = computed(() =>
    tasks.value.filter(t => !t.project_id && t.status !== 'done')
  )

  const todayTasks = computed(() => {
    const today = new Date().toISOString().split('T')[0]
    return tasks.value.filter(t => t.due_at?.startsWith(today))
  })

  const overdueTasks = computed(() => {
    const now = new Date()
    return tasks.value.filter(t => {
      if (!t.due_at || t.status === 'done') return false
      return new Date(t.due_at) < now
    })
  })

  // ============================================================
  // Fetch tasks
  // ============================================================
  async function fetchTasks(params: TaskFilters = {}) {
    isLoading.value = true
    try {
      const response = await apiClient.get(endpoints.tasks, { params })
      tasks.value = response.data.data
      pagination.value = response.data.pagination || pagination.value
      filters.value = params
    } finally {
      isLoading.value = false
    }
  }

  // ============================================================
  // Get single task
  // ============================================================
  async function fetchTask(id: number, include: string[] = []) {
    const response = await apiClient.get(endpoints.task(id), {
      params: { include: include.join(',') }
    })
    return response.data.data
  }

  // ============================================================
  // Create task (Optimistic update с откатом)
  // ============================================================
  async function createTask(payload: Partial<Task>) {
    const tempId = Date.now()
    const tempTask: Task = {
      ...payload,
      id: tempId,
      position: tasks.value.length * 1000,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
      deleted_at: null,
    } as Task

    tasks.value.unshift(tempTask)

    try {
      const response = await apiClient.post(endpoints.tasks, payload)
      const index = tasks.value.findIndex(t => t.id === tempId)
      if (index !== -1) {
        tasks.value[index] = response.data.data
      }
      uiStore.showToast('Задача создана', 'success')
      return response.data.data
    } catch (error: any) {
      tasks.value = tasks.value.filter(t => t.id !== tempId)
      uiStore.showToast('Не удалось создать задачу', 'error')
      throw error
    }
  }

  // ============================================================
  // Update task (Optimistic update с откатом)
  // ============================================================
  async function updateTask(id: number, payload: Partial<Task>) {
    const originalIndex = tasks.value.findIndex(t => t.id === id)
    const originalTask = originalIndex !== -1 ? { ...tasks.value[originalIndex] } : null

    if (originalIndex !== -1) {
      tasks.value[originalIndex] = { ...tasks.value[originalIndex], ...payload }
    }

    try {
      const response = await apiClient.put(endpoints.task(id), payload)
      tasks.value[originalIndex] = response.data.data
      uiStore.showToast('Задача обновлена', 'success')
      return response.data.data
    } catch (error: any) {
      if (originalIndex !== -1 && originalTask) {
        tasks.value[originalIndex] = originalTask
      }
      uiStore.showToast('Не удалось обновить задачу', 'error')
      throw error
    }
  }

  // ============================================================
  // Delete task (Optimistic remove с откатом)
  // ============================================================
  async function deleteTask(id: number) {
    const originalIndex = tasks.value.findIndex(t => t.id === id)
    const originalTask = originalIndex !== -1 ? tasks.value[originalIndex] : null

    tasks.value = tasks.value.filter(t => t.id !== id)

    try {
      await apiClient.delete(endpoints.task(id))
      uiStore.showToast('Задача удалена', 'success')
    } catch (error: any) {
      if (originalIndex !== -1 && originalTask) {
        tasks.value.splice(originalIndex, 0, originalTask)
      }
      uiStore.showToast('Не удалось удалить задачу', 'error')
      throw error
    }
  }

  // ============================================================
  // Complete task (Handles recurring logic)
  // ============================================================
  async function completeTask(id: number) {
    try {
      const response = await apiClient.post(endpoints.taskComplete(id))
      await fetchTasks(filters.value)
      uiStore.showToast('Задача выполнена!', 'success')
      return response.data.data
    } catch (error: any) {
      uiStore.showToast('Не удалось завершить задачу', 'error')
      throw error
    }
  }

  // ============================================================
  // Reorder task (Drag & Drop)
  // ============================================================
  async function reorderTask(id: number, position: number) {
    const task = tasks.value.find(t => t.id === id)
    const originalPosition = task?.position

    if (task) task.position = position

    try {
      await apiClient.put(endpoints.taskPosition(id), { position })
    } catch (error: any) {
      if (task) task.position = originalPosition || 0
      uiStore.showToast('Не удалось изменить порядок', 'error')
      throw error
    }
  }

  // ============================================================
  // WebSocket realtime (CRITICAL FIX #5)
  // ============================================================
  function setupRealtime(userId?: number, projectId?: number) {
    const { subscribeToUserChannel, subscribeToProjectChannel } = useWebSocket()

    if (userId) {
      subscribeToUserChannel('task.created', (payload: any) => {
        if (!tasks.value.find(t => t.id === payload.task.id)) {
          tasks.value.unshift(payload.task)
          uiStore.showToast(`Новая задача: ${payload.task.title}`, 'info')
        }
      })

      subscribeToUserChannel('task.updated', (payload: any) => {
        const index = tasks.value.findIndex(t => t.id === payload.task.id)
        if (index !== -1) {
          tasks.value[index] = payload.task
        }
      })

      subscribeToUserChannel('task.deleted', (payload: any) => {
        tasks.value = tasks.value.filter(t => t.id !== payload.task_id)
      })
    }

    if (projectId) {
      subscribeToProjectChannel(projectId, 'task.created', (payload: any) => {
        if (!tasks.value.find(t => t.id === payload.task.id)) {
          tasks.value.unshift(payload.task)
        }
      })

      subscribeToProjectChannel(projectId, 'task.updated', (payload: any) => {
        const index = tasks.value.findIndex(t => t.id === payload.task.id)
        if (index !== -1) {
          tasks.value[index] = payload.task
        }
      })

      subscribeToProjectChannel(projectId, 'task.deleted', (payload: any) => {
        tasks.value = tasks.value.filter(t => t.id !== payload.task_id)
      })
    }
  }

  // ============================================================
  // Subtasks
  // ============================================================
  async function fetchSubtasks(taskId: number) {
    const response = await apiClient.get(endpoints.taskSubtasks(taskId))
    return response.data.data
  }

  async function fetchSubtasksRecursive(taskId: number): Promise<Task[]> {
    const subtasks = await fetchSubtasks(taskId)
    const allSubtasks: Task[] = []
    
    for (const subtask of subtasks) {
      allSubtasks.push(subtask)
      // Recursively fetch nested subtasks
      const nested = await fetchSubtasksRecursive(subtask.id)
      allSubtasks.push(...nested)
    }
    
    return allSubtasks
  }

  async function createSubtask(taskId: number, payload: Partial<Task> & { parent_task_id?: number }) {
    const response = await apiClient.post(endpoints.taskSubtasks(taskId), payload)
    return response.data.data
  }

  async function updateSubtask(taskId: number, subId: number, payload: Partial<Task>) {
    const response = await apiClient.put(endpoints.taskSubtask(taskId, subId), payload)
    return response.data.data
  }

  async function deleteSubtask(taskId: number, subId: number) {
    await apiClient.delete(endpoints.taskSubtask(taskId, subId))
  }

  async function toggleSubtask(taskId: number, subId: number) {
    const subtask = tasks.value.find(t => t.id === subId)
    if (!subtask) return
    if (subtask.status === 'done') {
      await updateSubtask(taskId, subId, { status: 'todo' })
    } else {
      await updateSubtask(taskId, subId, { status: 'done' })
    }
  }

  /**
   * Move a task to another parent or to root level
   * @param taskId - ID of the task to move
   * @param newParentId - ID of the new parent task (null for root level)
   * @param position - Optional position in the new parent's subtasks
   */
  async function moveTask(taskId: number, newParentId: number | null, position?: number) {
    const response = await apiClient.patch(endpoints.taskMove(taskId), {
      new_parent_id: newParentId,
      position,
    })
    return response.data.data
  }

  return {
    tasks,
    isLoading,
    pagination,
    filters,
    inboxTasks,
    todayTasks,
    overdueTasks,
    fetchTasks,
    fetchTask,
    createTask,
    updateTask,
    deleteTask,
    completeTask,
    reorderTask,
    setupRealtime,
    // Subtasks
    fetchSubtasks,
    fetchSubtasksRecursive,
    createSubtask,
    updateSubtask,
    deleteSubtask,
    toggleSubtask,
    moveTask,
  }
})

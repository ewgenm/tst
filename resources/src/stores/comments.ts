// ============================================================
// Comments Store — ТЗ №2 v1.1 Этап 5
// ============================================================

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Comment } from '@/types'
import { apiClient } from '@/api/client'
import { endpoints } from '@/api/endpoints'
import { useUIStore } from '@/stores/ui'

export const useCommentsStore = defineStore('comments', () => {
  const comments = ref<Comment[]>([])
  const isLoading = ref(false)
  const isSubmitting = ref(false)

  const rootComments = computed(() => comments.value.filter(c => !c.parent_comment_id))

  function getReplies(parentCommentId: number): Comment[] {
    return comments.value.filter(c => c.parent_comment_id === parentCommentId)
  }

  async function fetchComments(taskId: number) {
    isLoading.value = true
    try {
      const response = await apiClient.get(endpoints.taskComments(taskId))
      comments.value = response.data.data
      return comments.value
    } finally { isLoading.value = false }
  }

  async function createComment(taskId: number, content: string, parentCommentId?: number) {
    const tempId = Date.now()
    const tempComment: Comment = {
      id: tempId, task_id: taskId, user_id: 0,
      parent_comment_id: parentCommentId || null, content,
      created_at: new Date().toISOString(), updated_at: new Date().toISOString(),
      user: undefined,
    } as Comment

    comments.value.push(tempComment)
    isSubmitting.value = true

    try {
      const payload: any = { content }
      if (parentCommentId) payload.parent_comment_id = parentCommentId
      const response = await apiClient.post(endpoints.taskComments(taskId), payload)
      const index = comments.value.findIndex(c => c.id === tempId)
      if (index !== -1) comments.value[index] = response.data.data
      return response.data.data
    } catch (error: any) {
      comments.value = comments.value.filter(c => c.id !== tempId)
      useUIStore().showToast('Не удалось добавить комментарий', 'error')
      throw error
    } finally { isSubmitting.value = false }
  }

  async function updateComment(commentId: number, content: string) {
    const originalIndex = comments.value.findIndex(c => c.id === commentId)
    const originalComment = originalIndex !== -1 ? { ...comments.value[originalIndex] } : null
    if (originalIndex !== -1) comments.value[originalIndex] = { ...comments.value[originalIndex], content }

    try {
      const response = await apiClient.put(endpoints.comment(commentId), { content })
      comments.value[originalIndex] = response.data.data
      return response.data.data
    } catch (error: any) {
      if (originalIndex !== -1 && originalComment) comments.value[originalIndex] = originalComment
      useUIStore().showToast('Не удалось обновить комментарий', 'error')
      throw error
    }
  }

  async function deleteComment(commentId: number) {
    const originalIndex = comments.value.findIndex(c => c.id === commentId)
    const originalComment = originalIndex !== -1 ? comments.value[originalIndex] : null
    comments.value = comments.value.filter(c => c.id !== commentId)

    try {
      await apiClient.delete(endpoints.comment(commentId))
      useUIStore().showToast('Комментарий удалён', 'success')
    } catch (error: any) {
      if (originalIndex !== -1 && originalComment) comments.value.splice(originalIndex, 0, originalComment)
      useUIStore().showToast('Не удалось удалить комментарий', 'error')
      throw error
    }
  }

  return {
    comments, isLoading, isSubmitting, rootComments, getReplies,
    fetchComments, createComment, updateComment, deleteComment,
  }
})

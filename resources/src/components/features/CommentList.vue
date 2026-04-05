<!-- CommentList — Комментарии с поддержкой ответов -->
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { Comment } from '@/types'
import { useCommentsStore } from '@/stores/comments'
import { useAuthStore } from '@/stores/auth'
import { useDateFormatter } from '@/composables/useDateFormatter'

interface Props { taskId: number }
const props = defineProps<Props>()
const commentsStore = useCommentsStore()
const authStore = useAuthStore()
const { formatDueRelative } = useDateFormatter()

const newComment = ref('')
const replyingTo = ref<number | null>(null)
const replyContent = ref('')
const editingComment = ref<number | null>(null)
const editContent = ref('')

onMounted(async () => { await commentsStore.fetchComments(props.taskId) })

const rootComments = computed(() => commentsStore.rootComments.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()))
function getReplies(parentId: number): Comment[] { return commentsStore.getReplies(parentId).sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()) }

async function submitComment() { if (!newComment.value.trim()) return; await commentsStore.createComment(props.taskId, newComment.value); newComment.value = '' }
async function submitReply(parentId: number) { if (!replyContent.value.trim()) return; await commentsStore.createComment(props.taskId, replyContent.value, parentId); replyContent.value = ''; replyingTo.value = null }
function startReply(parentId: number) { replyingTo.value = replyingTo.value === parentId ? null : parentId; replyContent.value = '' }
function startEdit(comment: Comment) { editingComment.value = comment.id; editContent.value = comment.content }
async function saveEdit(commentId: number) { if (!editContent.value.trim()) return; await commentsStore.updateComment(commentId, editContent.value); editingComment.value = null; editContent.value = '' }
function cancelEdit() { editingComment.value = null; editContent.value = '' }
async function deleteComment(commentId: number) { if (!confirm('Удалить комментарий?')) return; await commentsStore.deleteComment(commentId) }
function isCommentAuthor(comment: Comment): boolean { return comment.user_id === authStore.user?.id }
</script>

<template>
  <div>
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
      <h3 class="font-semibold mb-3">💬 Добавить комментарий</h3>
      <form @submit.prevent="submitComment">
        <textarea v-model="newComment" rows="3" placeholder="Напишите комментарий..." class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:ring-2 focus:ring-primary-500 resize-none" />
        <div class="flex justify-end mt-2">
          <button type="submit" :disabled="!newComment.trim() || commentsStore.isSubmitting" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50">{{ commentsStore.isSubmitting ? 'Отправка...' : 'Отправить' }}</button>
        </div>
      </form>
    </div>

    <div v-if="commentsStore.isLoading" class="text-center py-8"><div class="w-6 h-6 border-2 border-primary-500 border-t-transparent rounded-full animate-spin mx-auto"></div></div>
    <div v-else-if="rootComments.length === 0" class="text-center py-8 text-gray-500"><p>Комментариев пока нет</p></div>
    <div v-else class="space-y-4">
      <div v-for="comment in rootComments" :key="comment.id" class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
        <div class="p-4">
          <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">{{ comment.user?.name?.charAt(0).toUpperCase() || '?' }}</div>
              <div><p class="font-medium text-sm">{{ comment.user?.name || 'Загрузка...' }}</p><p class="text-xs text-gray-500">{{ formatDueRelative(comment.created_at) }}</p></div>
            </div>
            <div v-if="isCommentAuthor(comment)" class="flex gap-1">
              <button @click="startEdit(comment)" class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded" title="Редактировать"><svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg></button>
              <button @click="deleteComment(comment.id)" class="p-1.5 hover:bg-red-100 dark:hover:bg-red-900/20 rounded text-red-600" title="Удалить"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
            </div>
          </div>
          <div v-if="editingComment === comment.id" class="mb-3">
            <textarea v-model="editContent" rows="3" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 resize-none" />
            <div class="flex gap-2 mt-2"><button @click="saveEdit(comment.id)" class="px-3 py-1 bg-primary-600 text-white text-sm rounded">Сохранить</button><button @click="cancelEdit" class="px-3 py-1 border text-sm rounded">Отмена</button></div>
          </div>
          <div v-else class="prose prose-sm dark:prose-invert max-w-none mb-3"><p class="text-sm whitespace-pre-wrap">{{ comment.content }}</p></div>
          <button @click="startReply(comment.id)" class="text-sm text-primary-600 hover:underline">{{ replyingTo === comment.id ? 'Отменить' : 'Ответить' }}</button>
          <div v-if="replyingTo === comment.id" class="mt-3 ml-4 pl-4 border-l-2 border-gray-200 dark:border-gray-700">
            <form @submit.prevent="submitReply(comment.id)" class="mt-2"><textarea v-model="replyContent" rows="2" placeholder="Напишите ответ..." class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 resize-none text-sm" /><div class="flex justify-end mt-2"><button type="submit" :disabled="!replyContent.trim()" class="px-3 py-1 bg-primary-600 text-white text-sm rounded disabled:opacity-50">Ответить</button></div></form>
          </div>
        </div>
        <div v-if="getReplies(comment.id).length > 0" class="ml-8 pl-4 border-l-2 border-gray-200 dark:border-gray-700 space-y-3">
          <div v-for="reply in getReplies(comment.id)" :key="reply.id" class="p-3 bg-gray-50 dark:bg-gray-800/50">
            <div class="flex items-start justify-between mb-2">
              <div class="flex items-center gap-2"><div class="w-6 h-6 bg-gray-400 rounded-full flex items-center justify-center text-white text-xs font-semibold">{{ reply.user?.name?.charAt(0).toUpperCase() || '?' }}</div><div><p class="font-medium text-xs">{{ reply.user?.name || 'Загрузка...' }}</p><p class="text-xs text-gray-500">{{ formatDueRelative(reply.created_at) }}</p></div></div>
              <div v-if="isCommentAuthor(reply)" class="flex gap-1">
                <button @click="startEdit(reply)" class="p-1 hover:bg-gray-200 dark:hover:bg-gray-700 rounded"><svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg></button>
                <button @click="deleteComment(reply.id)" class="p-1 hover:bg-red-100 dark:hover:bg-red-900/20 rounded text-red-600"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
              </div>
            </div>
            <div v-if="editingComment === reply.id"><textarea v-model="editContent" rows="2" class="w-full px-2 py-1 border rounded text-sm dark:bg-gray-700 dark:border-gray-600 resize-none" /><div class="flex gap-2 mt-1"><button @click="saveEdit(reply.id)" class="px-2 py-0.5 bg-primary-600 text-white text-xs rounded">Сохранить</button><button @click="cancelEdit" class="px-2 py-0.5 border text-xs rounded">Отмена</button></div></div>
            <p v-else class="text-sm whitespace-pre-wrap">{{ reply.content }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

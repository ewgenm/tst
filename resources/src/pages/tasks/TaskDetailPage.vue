<!-- TaskDetailPage -->
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTasksStore } from '@/stores/tasks'
import { useAuthStore } from '@/stores/auth'
import { useDateFormatter } from '@/composables/useDateFormatter'
import { useToast } from '@/composables/useToast'
import CommentList from '@/components/features/CommentList.vue'
import AttachmentUploader from '@/components/features/AttachmentUploader.vue'
import SubtaskList from '@/components/features/SubtaskList.vue'

const route = useRoute()
const router = useRouter()
const tasksStore = useTasksStore()
const authStore = useAuthStore()
const { formatDueDate, isOverdue } = useDateFormatter()
const { show: showToast } = useToast()

const taskId = computed(() => Number(route.params.id))
const task = computed(() => tasksStore.tasks.find(t => t.id === taskId.value))
const subtasks = computed(() => task.value?.subtasks || [])

const isEditing = ref(false)
const editTitle = ref('')
const editDescription = ref('')
const editStatus = ref('todo')
const editPriority = ref('medium')
const editDueAt = ref('')
const activeTab = ref<'comments' | 'attachments' | 'activity'>('comments')

const statusOptions = [{ value: 'todo', label: 'К выполнению' }, { value: 'in_progress', label: 'В работе' }, { value: 'review', label: 'На ревью' }, { value: 'done', label: 'Выполнено' }]
const priorityOptions = [{ value: 'low', label: '🟢 Низкий', color: 'bg-gray-400' }, { value: 'medium', label: '🔵 Средний', color: 'bg-blue-500' }, { value: 'high', label: '🟠 Высокий', color: 'bg-amber-500' }, { value: 'urgent', label: '🔴 Срочный', color: 'bg-red-500' }]
const recurringOptions = [{ value: 'FREQ=DAILY', label: 'Ежедневно' }, { value: 'FREQ=WEEKLY', label: 'Еженедельно' }, { value: 'FREQ=MONTHLY', label: 'Ежемесячно' }, { value: 'FREQ=YEARLY', label: 'Ежегодно' }]
const getStatusBadge = (s: string) => ({ todo: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', in_progress: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', review: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400', done: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' }[s] || '')
const getStatusLabel = (s: string) => ({ todo: 'К выполнению', in_progress: 'В работе', review: 'На ревью', done: 'Выполнено' }[s] || s)

onMounted(async () => { await tasksStore.fetchTask(taskId.value, ['project', 'assignee', 'tags', 'attachments', 'subtasks']); if (task.value) { editTitle.value = task.value.title; editDescription.value = task.value.description || ''; editStatus.value = task.value.status; editPriority.value = task.value.priority; editDueAt.value = task.value.due_at || '' } })

function startEdit() { isEditing.value = true }
function cancelEdit() { isEditing.value = false; if (task.value) { editTitle.value = task.value.title; editDescription.value = task.value.description || ''; editStatus.value = task.value.status; editPriority.value = task.value.priority; editDueAt.value = task.value.due_at || '' } }
async function saveTask() { if (!editTitle.value.trim()) return; await tasksStore.updateTask(taskId.value, { title: editTitle.value, description: editDescription.value || null, status: editStatus.value, priority: editPriority.value, due_at: editDueAt.value || null }); isEditing.value = false; showToast('Задача обновлена', 'success') }
async function toggleTaskStatus() { if (!task.value) return; if (task.value.status === 'done') await tasksStore.updateTask(taskId.value, { status: 'todo' }); else await tasksStore.completeTask(taskId.value) }
async function deleteTask() { if (!confirm('Удалить задачу?')) return; await tasksStore.deleteTask(taskId.value); router.push('/') }
</script>

<template>
  <div class="max-w-6xl mx-auto" v-if="task">
    <div class="flex items-center gap-2 mb-4 text-sm text-gray-500"><router-link to="/" class="hover:text-primary-600">Входящие</router-link><span>/</span><span class="text-gray-900 dark:text-gray-100">{{ task.title }}</span></div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6 mb-6">
      <div class="flex items-start justify-between gap-4">
        <div class="flex-1">
          <div class="flex items-start gap-3 mb-4">
            <button @click="toggleTaskStatus" class="mt-1 w-6 h-6 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-colors" :class="task.status === 'done' ? 'bg-green-500 border-green-500' : 'border-gray-300 dark:border-gray-600 hover:border-primary-500'"><svg v-if="task.status === 'done'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg></button>
            <div class="flex-1"><h1 v-if="!isEditing" class="text-2xl font-bold" :class="{ 'line-through opacity-75': task.status === 'done' }">{{ task.title }}</h1><input v-else v-model="editTitle" type="text" class="w-full text-2xl font-bold px-3 py-1 border rounded-lg dark:bg-gray-700 dark:border-gray-600" /></div>
          </div>
          <div class="flex items-center gap-2 flex-wrap mb-4">
            <span class="px-3 py-1 rounded-full text-xs font-medium" :class="getStatusBadge(task.status)">{{ getStatusLabel(task.status) }}</span>
            <span class="flex items-center gap-1 text-sm"><span class="w-3 h-3 rounded-full" :class="priorityOptions.find(p => p.value === task.priority)?.color" /></span>
            <span v-if="task.due_at" class="flex items-center gap-1 text-sm" :class="{ 'text-red-600 dark:text-red-400 font-medium': isOverdue(task.due_at) && task.status !== 'done' }"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>{{ formatDueDate(task.due_at) }}</span>
            <router-link v-if="task.project" :to="`/projects/${task.project.id}`" class="text-sm text-primary-600 hover:underline">📁 {{ task.project.name }}</router-link>
            <span v-if="task.assignee" class="text-sm text-gray-500">👤 {{ task.assignee.name }}</span>
          </div>
        </div>
        <div class="flex gap-2"><button v-if="!isEditing" @click="startEdit" class="px-3 py-1.5 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">✏️ Редактировать</button><button @click="deleteTask" class="px-3 py-1.5 border border-red-500 text-red-500 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-sm">🗑️</button></div>
      </div>
      <div v-if="isEditing" class="flex gap-2 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700"><button @click="saveTask" :disabled="!editTitle.trim()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50">Сохранить</button><button @click="cancelEdit" class="px-4 py-2 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Отмена</button></div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6"><h2 class="text-lg font-semibold mb-3">📝 Описание</h2><div v-if="!isEditing" class="prose prose-sm dark:prose-invert max-w-none"><p v-if="task.description" class="whitespace-pre-wrap">{{ task.description }}</p><p v-else class="text-gray-500 italic">Нет описания</p></div><textarea v-else v-model="editDescription" rows="6" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 resize-none" /></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6"><SubtaskList :parent-task-id="task.id" :subtasks="subtasks" /></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
          <div class="flex border-b border-gray-200 dark:border-gray-700"><button @click="activeTab = 'comments'" class="flex-1 px-4 py-3 text-sm font-medium border-b-2" :class="activeTab === 'comments' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500'">💬 Комментарии ({{ task.comments_count || 0 }})</button><button @click="activeTab = 'attachments'" class="flex-1 px-4 py-3 text-sm font-medium border-b-2" :class="activeTab === 'attachments' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500'">📎 Вложения ({{ task.attachments_count || 0 }})</button></div>
          <div class="p-6"><CommentList v-if="activeTab === 'comments'" :task-id="task.id" /><AttachmentUploader v-if="activeTab === 'attachments'" :task-id="task.id" :attachments="task.attachments" /></div>
        </div>
      </div>
      <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6"><h3 class="font-semibold mb-4">ℹ️ Информация</h3><dl class="space-y-3 text-sm"><div><dt class="text-gray-500">Создана</dt><dd class="font-medium">{{ formatDueDate(task.created_at) }}</dd></div><div><dt class="text-gray-500">Обновлена</dt><dd class="font-medium">{{ formatDueDate(task.updated_at) }}</dd></div></dl></div>
        <div v-if="isEditing" class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6"><h3 class="font-semibold mb-4">⚙️ Настройки</h3><div class="space-y-4"><div><label class="block text-sm font-medium mb-1">Статус</label><select v-model="editStatus" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"><option v-for="o in statusOptions" :key="o.value" :value="o.value">{{ o.label }}</option></select></div><div><label class="block text-sm font-medium mb-1">Приоритет</label><select v-model="editPriority" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"><option v-for="o in priorityOptions" :key="o.value" :value="o.value">{{ o.label }}</option></select></div><div><label class="block text-sm font-medium mb-1">Срок</label><input v-model="editDueAt" type="datetime-local" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" /></div></div></div>
      </div>
    </div>
  </div>
  <div v-else class="text-center py-12"><div class="w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full animate-spin mx-auto"></div><p class="text-gray-500 mt-4">Загрузка задачи...</p></div>
</template>

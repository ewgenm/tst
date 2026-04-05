<!-- ProjectDetailPage — с управлением задачами (создание, редактирование, удаление) -->
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProjectsStore } from '@/stores/projects'
import { useTasksStore } from '@/stores/tasks'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import TaskList from '@/components/features/TaskList.vue'
import TaskForm from '@/components/features/TaskForm.vue'

const route = useRoute()
const router = useRouter()
const projectsStore = useProjectsStore()
const tasksStore = useTasksStore()
const authStore = useAuthStore()
const { show: showToast } = useToast()

const activeTab = ref<'tasks' | 'members' | 'settings'>('tasks')
const inviteEmail = ref('')
const inviteRole = ref<'admin' | 'member' | 'viewer'>('member')
const isInviting = ref(false)
const isEditing = ref(false)
const editName = ref('')
const editDescription = ref('')
const editColor = ref('#3B82F6')

// Управление задачами
const showTaskForm = ref(false)
const editingTaskId = ref<number | null>(null)

const projectId = computed(() => Number(route.params.id))
const isOwner = computed(() => projectsStore.currentProject?.owner_id === authStore.user?.id)
const isAdmin = computed(() => { const member = projectsStore.members.find(m => m.user_id === authStore.user?.id); return member?.role === 'admin' || isOwner.value })

const roleLabels: Record<string, string> = { admin: 'Администратор', member: 'Участник', viewer: 'Наблюдатель' }
const roleColors: Record<string, string> = { admin: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', member: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', viewer: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400' }

onMounted(async () => {
  await projectsStore.fetchProject(projectId.value, ['owner'])
  await projectsStore.fetchMembers(projectId.value)
  await tasksStore.fetchTasks({ project_id: projectId.value })
  if (projectsStore.currentProject) {
    editName.value = projectsStore.currentProject.name
    editDescription.value = projectsStore.currentProject.description || ''
    editColor.value = projectsStore.currentProject.color
  }
})

async function inviteMember() {
  if (!inviteEmail.value.trim()) return
  isInviting.value = true
  try {
    await projectsStore.inviteMember(projectId.value, inviteEmail.value, inviteRole.value)
    inviteEmail.value = ''
  } finally { isInviting.value = false }
}

async function removeMember(memberId: number) {
  if (!confirm('Удалить участника?')) return
  await projectsStore.removeMember(projectId.value, memberId)
}

async function saveProjectSettings() {
  await projectsStore.updateProject(projectId.value, {
    name: editName.value,
    description: editDescription.value || null,
    color: editColor.value,
  })
  isEditing.value = false
  showToast('Проект обновлён', 'success')
}

async function archiveProject() {
  if (!confirm('Архивировать проект?')) return
  await projectsStore.archiveProject(projectId.value)
  router.push('/projects')
}

async function deleteProject() {
  if (!confirm('Удалить проект?')) return
  await projectsStore.deleteProject(projectId.value)
  router.push('/projects')
}

async function leaveProject() {
  if (!confirm('Покинуть проект?')) return
  await projectsStore.leaveProject(projectId.value)
  router.push('/projects')
}

// Управление задачами
function openCreateTask() {
  editingTaskId.value = null
  showTaskForm.value = true
}

function openEditTask(taskId: number) {
  editingTaskId.value = taskId
  showTaskForm.value = true
}

function handleTaskCreated() {
  showTaskForm.value = false
  editingTaskId.value = null
  showToast('Задача создана', 'success')
}

function handleTaskUpdated() {
  showTaskForm.value = false
  editingTaskId.value = null
  showToast('Задача обновлена', 'success')
}

async function handleTaskDeleted(id: number) {
  if (!confirm('Удалить задачу?')) return
  await tasksStore.deleteTask(id)
  showToast('Задача удалена', 'success')
}
</script>

<template>
  <div class="max-w-6xl mx-auto" v-if="projectsStore.currentProject">
    <!-- Шапка проекта -->
    <div class="flex items-start justify-between mb-6">
      <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-lg flex items-center justify-center text-white text-2xl" :style="{ backgroundColor: projectsStore.currentProject.color }">
          {{ projectsStore.currentProject.icon || '📁' }}
        </div>
        <div>
          <h1 class="text-2xl font-bold">{{ projectsStore.currentProject.name }}</h1>
          <p v-if="projectsStore.currentProject.description" class="text-sm text-gray-500 mt-1">
            {{ projectsStore.currentProject.description }}
          </p>
        </div>
      </div>
      <div class="flex gap-2">
        <button v-if="!isOwner" @click="leaveProject" class="px-4 py-2 border border-red-500 text-red-500 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20">
          Покинуть проект
        </button>
        <button v-if="isOwner" @click="isEditing = !isEditing" class="px-4 py-2 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
          Редактировать
        </button>
      </div>
    </div>

    <!-- Форма редактирования проекта -->
    <div v-if="isEditing" class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
      <h2 class="text-lg font-semibold mb-4">Редактировать проект</h2>
      <form @submit.prevent="saveProjectSettings" class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Название</label>
          <input v-model="editName" type="text" required class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Описание</label>
          <textarea v-model="editDescription" rows="2" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Цвет</label>
          <input v-model="editColor" type="color" class="w-full h-10 rounded-lg border border-gray-300 dark:border-gray-600 cursor-pointer" />
        </div>
        <div class="flex gap-2">
          <button type="submit" class="flex-1 bg-primary-600 text-white py-2 rounded-lg">Сохранить</button>
          <button type="button" @click="isEditing = false" class="px-4 py-2 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Отмена</button>
        </div>
      </form>
    </div>

    <!-- Вкладки -->
    <div class="flex gap-2 mb-6 border-b border-gray-200 dark:border-gray-700">
      <button @click="activeTab = 'tasks'" class="px-4 py-2 border-b-2 font-medium" :class="activeTab === 'tasks' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500'">
        Задачи ({{ tasksStore.tasks.length }})
      </button>
      <button @click="activeTab = 'members'" class="px-4 py-2 border-b-2 font-medium" :class="activeTab === 'members' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500'">
        Участники ({{ projectsStore.members.length }})
      </button>
      <button v-if="isOwner" @click="activeTab = 'settings'" class="px-4 py-2 border-b-2 font-medium" :class="activeTab === 'settings' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500'">
        Настройки
      </button>
    </div>

    <!-- Вкладка: Задачи -->
    <div v-if="activeTab === 'tasks'">
      <!-- Кнопка создания задачи -->
      <div class="mb-4 flex justify-between items-center">
        <h2 class="text-lg font-semibold">Задачи проекта</h2>
        <button @click="openCreateTask" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Новая задача
        </button>
      </div>

      <!-- Форма создания/редактирования задачи -->
      <div v-if="showTaskForm" class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold">{{ editingTaskId ? 'Редактировать задачу' : 'Новая задача' }}</h2>
          <button @click="showTaskForm = false; editingTaskId = null" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <TaskForm
          :default-data="editingTaskId ? tasksStore.tasks.find(t => t.id === editingTaskId) : undefined"
          :project-id="projectId"
          @created="handleTaskCreated"
          @updated="handleTaskUpdated"
          @cancel="showTaskForm = false; editingTaskId = null"
        />
      </div>

      <!-- Список задач -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4">
        <div v-if="tasksStore.tasks.length === 0 && !tasksStore.isLoading" class="text-center py-12 text-gray-500">
          <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          <p class="text-lg font-medium">Задач пока нет</p>
          <p class="text-sm mt-2">Создайте первую задачу</p>
        </div>
        <div v-else>
          <div v-for="task in tasksStore.tasks" :key="task.id" class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors group">
            <!-- Чекбокс статуса -->
            <button @click="task.status === 'done' ? tasksStore.updateTask(task.id, { status: 'todo' }) : tasksStore.completeTask(task.id)" class="mt-0.5 w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-colors" :class="task.status === 'done' ? 'bg-green-500 border-green-500' : 'border-gray-300 dark:border-gray-600 hover:border-primary-500'">
              <svg v-if="task.status === 'done'" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </button>

            <!-- Контент задачи -->
            <div class="flex-1 min-w-0 cursor-pointer" @click="openEditTask(task.id)">
              <div class="flex items-center gap-2">
                <span class="text-sm font-medium truncate" :class="task.status === 'done' ? 'line-through opacity-75' : ''">
                  {{ task.title }}
                </span>
                <span v-if="task.priority === 'urgent'" class="w-2 h-2 rounded-full bg-red-500" />
                <span v-else-if="task.priority === 'high'" class="w-2 h-2 rounded-full bg-amber-500" />
              </div>
              <div v-if="task.due_at" class="text-xs text-gray-500 mt-1">
                📅 {{ new Date(task.due_at).toLocaleDateString('ru-RU') }}
              </div>
            </div>

            <!-- Действия -->
            <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1">
              <button @click="openEditTask(task.id)" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-700 rounded" title="Редактировать">
                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
              </button>
              <button @click="handleTaskDeleted(task.id)" class="p-1.5 hover:bg-red-100 dark:hover:bg-red-900/20 rounded text-red-600" title="Удалить">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Вкладка: Участники -->
    <div v-if="activeTab === 'members'">
      <div v-if="isAdmin" class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-semibold mb-4">Пригласить участника</h2>
        <form @submit.prevent="inviteMember" class="flex gap-3">
          <input v-model="inviteEmail" type="email" required placeholder="Email пользователя" class="flex-1 px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
          <select v-model="inviteRole" class="px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600">
            <option value="admin">Администратор</option>
            <option value="member">Участник</option>
            <option value="viewer">Наблюдатель</option>
          </select>
          <button type="submit" :disabled="isInviting" class="px-4 py-2 bg-primary-600 text-white rounded-lg disabled:opacity-50">
            {{ isInviting ? 'Отправка...' : 'Пригласить' }}
          </button>
        </form>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
        <div v-for="member in projectsStore.members" :key="member.id" class="flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700 last:border-0">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-primary-500 rounded-full flex items-center justify-center text-white font-semibold">
              {{ member.user?.name?.charAt(0).toUpperCase() || '?' }}
            </div>
            <div>
              <p class="font-medium">{{ member.user?.name || 'Загрузка...' }}</p>
              <p class="text-xs text-gray-500">{{ member.user?.email }}</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <span class="px-3 py-1 rounded-full text-xs font-medium" :class="roleColors[member.role]">
              {{ roleLabels[member.role] }}
            </span>
            <span v-if="member.status === 'pending'" class="px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
              Ожидает
            </span>
            <button v-if="isAdmin && member.user_id !== authStore.user?.id" @click="removeMember(member.id)" class="p-1.5 hover:bg-red-100 dark:hover:bg-red-900/20 rounded text-red-600">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Вкладка: Настройки -->
    <div v-if="activeTab === 'settings' && isOwner" class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6 space-y-4">
      <h2 class="text-lg font-semibold">Опасные действия</h2>
      <div class="flex items-center justify-between p-4 border border-amber-200 dark:border-amber-800 rounded-lg bg-amber-50 dark:bg-amber-900/20">
        <div>
          <h3 class="font-medium">Архивировать проект</h3>
          <p class="text-sm text-gray-500">Проект будет скрыт из основного списка</p>
        </div>
        <button @click="archiveProject" class="px-4 py-2 border border-amber-500 text-amber-600 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/30">
          Архивировать
        </button>
      </div>
      <div class="flex items-center justify-between p-4 border border-red-200 dark:border-red-800 rounded-lg bg-red-50 dark:bg-red-900/20">
        <div>
          <h3 class="font-medium">Удалить проект</h3>
          <p class="text-sm text-gray-500">Все задачи и данные будут удалены</p>
        </div>
        <button @click="deleteProject" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
          Удалить
        </button>
      </div>
    </div>
  </div>
  <div v-else class="text-center py-12">
    <div class="w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
  </div>
</template>

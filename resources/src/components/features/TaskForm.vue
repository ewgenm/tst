<!-- TaskForm с recurring support и subtasks (CRITICAL FIX #10) -->
<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import type { Task } from '@/types'
import { useTasksStore } from '@/stores/tasks'
import { useToast } from '@/composables/useToast'

interface SubtaskDraft { title: string; status: string; priority: string }

interface Props { defaultData?: Partial<Task>; projectId?: number | null }
const props = withDefaults(defineProps<Props>(), { defaultData: () => ({}), projectId: undefined })
const emit = defineEmits<{ created: []; updated: []; cancel: [] }>()

const tasksStore = useTasksStore()
const { show: showToast } = useToast()

const title = ref(props.defaultData?.title || '')
const description = ref(props.defaultData?.description || '')
const priority = ref(props.defaultData?.priority || 'medium')
const status = ref(props.defaultData?.status || 'todo')
const dueAt = ref(props.defaultData?.due_at || '')
const isRecurring = ref(props.defaultData?.is_recurring || false)
const recurringRule = ref(props.defaultData?.recurring_rule || 'FREQ=DAILY')
const errors = ref<Record<string, string[]>>({})

// Subtasks management
const showSubtasks = ref(false)
const subtasks = ref<SubtaskDraft[]>(
  props.defaultData?.subtasks?.map(s => ({ title: s.title, status: s.status, priority: s.priority || 'medium' })) || []
)
const newSubtaskTitle = ref('')

const recurringOptions = [
  { value: 'FREQ=DAILY', label: 'Ежедневно' },
  { value: 'FREQ=WEEKLY', label: 'Еженедельно' },
  { value: 'FREQ=MONTHLY', label: 'Ежемесячно' },
  { value: 'FREQ=YEARLY', label: 'Ежегодно' },
]

function addSubtask() {
  if (!newSubtaskTitle.value.trim()) return
  subtasks.value.push({ title: newSubtaskTitle.value.trim(), status: 'todo', priority: 'medium' })
  newSubtaskTitle.value = ''
}

function removeSubtask(index: number) {
  subtasks.value.splice(index, 1)
}

const dragSubtaskIndex = ref(-1)

function onSubtaskDragStart(index: number) {
  dragSubtaskIndex.value = index
}

function onSubtaskDrop(index: number) {
  if (dragSubtaskIndex.value === -1 || dragSubtaskIndex.value === index) return
  const [moved] = subtasks.value.splice(dragSubtaskIndex.value, 1)
  subtasks.value.splice(index, 0, moved)
  dragSubtaskIndex.value = -1
}

function handleValidationErrors(event: Event) { errors.value = (event as CustomEvent<Record<string, string[]>>).detail }
onMounted(() => window.addEventListener('validation-errors', handleValidationErrors))
onUnmounted(() => window.removeEventListener('validation-errors', handleValidationErrors))

async function submit() {
  errors.value = {}
  const payload: Partial<Task> = {
    title: title.value, description: description.value || null, priority: priority.value, status: status.value,
    due_at: dueAt.value || null, is_recurring: isRecurring.value, recurring_rule: isRecurring.value ? recurringRule.value : null,
    project_id: props.projectId ?? props.defaultData?.project_id ?? null,
  }
  
  try {
    if (props.defaultData?.id) {
      await tasksStore.updateTask(props.defaultData.id, payload)
      
      // Обработка подзадач при обновлении задачи
      if (subtasks.value.length > 0) {
        for (const sub of subtasks.value) {
          await tasksStore.createSubtask(props.defaultData!.id!, {
            title: sub.title,
            status: sub.status as Task['status'],
            priority: sub.priority as Task['priority'],
          })
        }
        showToast(`Добавлено ${subtasks.value.length} подзадач`, 'success')
      }
      
      emit('updated')
    } else {
      const createdTask = await tasksStore.createTask(payload)
      
      // Создание подзадач после создания задачи
      if (subtasks.value.length > 0 && createdTask.id) {
        for (const sub of subtasks.value) {
          await tasksStore.createSubtask(createdTask.id, {
            title: sub.title,
            status: sub.status as Task['status'],
            priority: sub.priority as Task['priority'],
          })
        }
        showToast(`Задача создана + ${subtasks.value.length} подзадач`, 'success')
      } else {
        showToast('Задача создана', 'success')
      }
      
      emit('created')
      title.value = ''
      description.value = ''
      dueAt.value = ''
      subtasks.value = []
    }
  } catch { /* handled */ }
}
</script>

<template>
  <form @submit.prevent="submit" class="space-y-4">
    <div>
      <label class="block text-sm font-medium mb-1">Заголовок <span class="text-red-500">*</span></label>
      <input v-model="title" type="text" required maxlength="255" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:ring-2 focus:ring-primary-500" :class="{ 'border-red-500': errors.title }" placeholder="Название задачи..." autofocus />
      <p v-if="errors.title" class="text-red-500 text-xs mt-1">{{ errors.title[0] }}</p>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Описание</label>
      <textarea v-model="description" rows="3" maxlength="10000" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" placeholder="Подробности..." />
    </div>
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Приоритет</label>
        <select v-model="priority" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600">
          <option value="low">🟢 Низкий</option><option value="medium">🔵 Средний</option><option value="high">🟠 Высокий</option><option value="urgent">🔴 Срочный</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Статус</label>
        <select v-model="status" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600">
          <option value="todo">К выполнению</option><option value="in_progress">В работе</option><option value="review">На ревью</option><option value="done">Выполнено</option>
        </select>
      </div>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Срок выполнения</label>
      <input v-model="dueAt" type="datetime-local" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" />
    </div>
    <div class="border-t dark:border-gray-700 pt-4">
      <label class="flex items-center gap-2 cursor-pointer"><input v-model="isRecurring" type="checkbox" class="w-4 h-4 rounded" /><span class="text-sm font-medium">🔁 Повторяющаяся задача</span></label>
      <div v-if="isRecurring" class="mt-2">
        <label class="block text-sm font-medium mb-1">Периодичность</label>
        <select v-model="recurringRule" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600">
          <option v-for="option in recurringOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
        </select>
      </div>
    </div>
    <div class="flex gap-2 pt-2">
      <button type="submit" :disabled="tasksStore.isLoading" class="flex-1 bg-primary-600 text-white py-2 rounded-lg hover:bg-primary-700 disabled:opacity-50">{{ tasksStore.isLoading ? 'Сохранение...' : (defaultData?.id ? 'Обновить' : 'Создать') }}</button>
      <button type="button" @click="emit('cancel')" class="px-4 py-2 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Отмена</button>
    </div>

    <!-- Секция подзадач -->
    <div class="border-t dark:border-gray-700 pt-4">
      <button
        type="button"
        @click="showSubtasks = !showSubtasks"
        class="flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-primary-500 transition-colors"
      >
        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': showSubtasks }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        📋 Подзадачи ({{ subtasks.length }})
      </button>

      <Transition
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="opacity-0 max-h-0"
        enter-to-class="opacity-100 max-h-96 overflow-hidden"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="opacity-100 max-h-96 overflow-hidden"
        leave-to-class="opacity-0 max-h-0"
      >
        <div v-if="showSubtasks" class="mt-3 space-y-2">
          <!-- Список подзадач -->
          <div
            v-for="(sub, index) in subtasks"
            :key="index"
            class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg group/subtask"
          >
            <!-- Drag handle -->
            <div
              class="drag-handle flex items-center justify-center w-5 h-5 rounded cursor-grab hover:bg-gray-200 dark:hover:bg-gray-600 opacity-0 group-hover/subtask:opacity-100 transition-opacity flex-shrink-0"
              draggable="true"
              @dragstart="onSubtaskDragStart(index)"
              @dragover.prevent
              @drop="onSubtaskDrop(index)"
              title="Перетащить"
            >
              <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z" />
              </svg>
            </div>

            <!-- Номер -->
            <span class="text-xs text-gray-400 w-5 text-center">{{ index + 1 }}</span>

            <!-- Название -->
            <span class="flex-1 text-sm truncate">{{ sub.title }}</span>

            <!-- Статус -->
            <select
              v-model="sub.status"
              class="text-xs px-1.5 py-0.5 bg-transparent border border-gray-200 dark:border-gray-600 rounded focus:outline-none"
            >
              <option value="todo">К выполнению</option>
              <option value="in_progress">В работе</option>
              <option value="done">Выполнено</option>
            </select>

            <!-- Приоритет -->
            <select
              v-model="sub.priority"
              class="text-xs px-1.5 py-0.5 bg-transparent border border-gray-200 dark:border-gray-600 rounded focus:outline-none"
            >
              <option value="low">🟢</option>
              <option value="medium">🔵</option>
              <option value="high">🟠</option>
              <option value="urgent">🔴</option>
            </select>

            <!-- Удалить -->
            <button
              type="button"
              @click="removeSubtask(index)"
              class="p-0.5 hover:text-red-500 opacity-0 group-hover/subtask:opacity-100 transition-opacity"
              title="Удалить подзадачу"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Форма добавления -->
          <div class="flex items-center gap-2">
            <input
              v-model="newSubtaskTitle"
              @keydown.enter.stop.prevent="addSubtask"
              type="text"
              placeholder="Название подзадачи..."
              class="flex-1 text-sm px-2 py-1.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary-500"
            />
            <button
              type="button"
              @click="addSubtask"
              class="px-3 py-1.5 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50"
              :disabled="!newSubtaskTitle.trim()"
            >
              Добавить
            </button>
          </div>
        </div>
      </Transition>
    </div>
  </form>
</template>

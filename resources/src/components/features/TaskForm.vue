<!-- TaskForm с recurring support (CRITICAL FIX #10) -->
<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import type { Task } from '@/types'
import { useTasksStore } from '@/stores/tasks'
import { useToast } from '@/composables/useToast'

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

const recurringOptions = [
  { value: 'FREQ=DAILY', label: 'Ежедневно' },
  { value: 'FREQ=WEEKLY', label: 'Еженедельно' },
  { value: 'FREQ=MONTHLY', label: 'Ежемесячно' },
  { value: 'FREQ=YEARLY', label: 'Ежегодно' },
]

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
    if (props.defaultData?.id) { await tasksStore.updateTask(props.defaultData.id, payload); emit('updated') }
    else { await tasksStore.createTask(payload); showToast('Задача создана', 'success'); emit('created'); title.value = ''; description.value = ''; dueAt.value = '' }
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
  </form>
</template>

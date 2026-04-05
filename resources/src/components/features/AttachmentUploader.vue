<!-- AttachmentUploader компонент -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import type { Attachment } from '@/types'
import { apiClient } from '@/api/client'
import { endpoints } from '@/api/endpoints'
import { useToast } from '@/composables/useToast'

interface Props { taskId: number; attachments?: Attachment[] }
const props = defineProps<Props>()
const { show: showToast } = useToast()
const isUploading = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)
const attachmentsList = computed(() => props.attachments || [])

const allowedTypes = 'image/jpeg,image/png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip'
const maxSize = 10 * 1024 * 1024

function triggerFileSelect() { fileInput.value?.click() }
async function handleFileSelect(event: Event) { const target = event.target as HTMLInputElement; const files = target.files; if (!files) return; for (const file of files) await uploadFile(file); if (fileInput.value) fileInput.value.value = '' }
async function uploadFile(file: File) {
  if (file.size > maxSize) { showToast(`Файл ${file.name} слишком большой (макс. 10MB)`, 'error'); return }
  isUploading.value = true
  try {
    const formData = new FormData(); formData.append('file', file)
    await apiClient.post(endpoints.taskAttachments(props.taskId), formData, { headers: { 'Content-Type': 'multipart/form-data' } })
    showToast(`Файл ${file.name} загружен`, 'success')
  } catch { showToast(`Ошибка загрузки ${file.name}`, 'error') }
  finally { isUploading.value = false }
}
async function deleteAttachment(attachmentId: number) { if (!confirm('Удалить вложение?')) return; try { await apiClient.delete(endpoints.attachment(attachmentId)); showToast('Вложение удалено', 'success') } catch { showToast('Не удалось удалить вложение', 'error') } }
function formatFileSize(bytes: number): string { if (bytes < 1024) return bytes + ' B'; if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'; return (bytes / (1024 * 1024)).toFixed(1) + ' MB' }
function getFileIcon(mimeType: string): string { if (mimeType.startsWith('image/')) return '🖼️'; if (mimeType === 'application/pdf') return '📄'; if (mimeType.includes('word')) return '📝'; if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return '📊'; if (mimeType === 'application/zip') return '📦'; return '📎' }
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold flex items-center gap-2">📎 Вложения <span v-if="attachmentsList.length > 0" class="text-sm font-normal text-gray-500">({{ attachmentsList.length }})</span></h3>
      <button @click="triggerFileSelect" :disabled="isUploading" class="text-sm text-primary-600 hover:underline disabled:opacity-50">{{ isUploading ? 'Загрузка...' : '+ Загрузить' }}</button>
    </div>
    <input ref="fileInput" type="file" multiple :accept="allowedTypes" class="hidden" @change="handleFileSelect" />
    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:border-primary-500 transition-colors cursor-pointer" :class="{ 'border-primary-500 bg-primary-50 dark:bg-primary-900/10': isUploading }" @click="triggerFileSelect" @dragover.prevent @drop.prevent="(e: DragEvent) => { const files = e.dataTransfer?.files; if (files) for (const file of files) uploadFile(file) }">
      <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
      <p class="text-sm text-gray-500">Перетащите файлы сюда или <span class="text-primary-600">выберите</span></p>
      <p class="text-xs text-gray-400 mt-1">JPG, PNG, PDF, DOC, XLS, ZIP (макс. 10MB)</p>
    </div>
    <div v-if="attachmentsList.length > 0" class="mt-4 space-y-2">
      <div v-for="attachment in attachmentsList" :key="attachment.id" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg group hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
        <div class="text-2xl flex-shrink-0">{{ getFileIcon(attachment.mime_type) }}</div>
        <div class="flex-1 min-w-0"><a :href="attachment.url" target="_blank" class="text-sm font-medium truncate block hover:text-primary-600">{{ attachment.filename }}</a><p class="text-xs text-gray-500">{{ formatFileSize(attachment.size) }} • {{ attachment.mime_type }}</p></div>
        <button @click="deleteAttachment(attachment.id)" class="opacity-0 group-hover:opacity-100 p-1.5 hover:bg-red-100 dark:hover:bg-red-900/20 rounded text-red-600 transition-all" title="Удалить"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
      </div>
    </div>
  </div>
</template>

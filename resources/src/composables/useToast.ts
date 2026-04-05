// ============================================================
// useToast composable (ТЗ №2 v1.1 — раздел 4.4)
// ============================================================

import { useUIStore } from '@/stores/ui'

export function useToast() {
  const uiStore = useUIStore()

  function show(message: string, type: 'success' | 'error' | 'warning' | 'info' = 'info') {
    uiStore.showToast(message, type)
  }

  function hide() {
    uiStore.hideToast()
  }

  return { show, hide }
}

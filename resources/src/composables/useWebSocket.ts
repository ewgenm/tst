// ============================================================
// useWebSocket composable — Laravel Echo + Reverb
// ТЗ №2 v1.1 — раздел 4.2 (CRITICAL FIX #11)
// ============================================================

import Echo from 'laravel-echo'
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

export type WsEventName =
  | 'task.created' | 'task.updated' | 'task.deleted' | 'task.assigned'
  | 'comment.created' | 'project.member.added' | 'notification.new'

// eslint-disable-next-line @typescript-eslint/no-explicit-any
let echoInstance: any | null = null

export function useWebSocket() {
  const authStore = useAuthStore()
  const isConnected = ref(false)
  const subscribedChannels = ref<string[]>([])

  function init() {
    if (echoInstance || !authStore.isAuthenticated) return

    echoInstance = new Echo({
      broadcaster: 'reverb',
      key: import.meta.env.VITE_REVERB_APP_KEY || 'tasksync-key',
      wsHost: import.meta.env.VITE_REVERB_HOST || 'tst.test',
      wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
      wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
      forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
      enabledTransports: ['ws', 'wss'],
    })

    echoInstance.connector.pusher.connection.bind('connected', () => { isConnected.value = true })
    echoInstance.connector.pusher.connection.bind('disconnected', () => { isConnected.value = false })
    echoInstance.connector.pusher.connection.bind('error', (error: any) => {
      console.error('[WebSocket] Error:', error)
      isConnected.value = false
    })
  }

  function subscribeToUserChannel(event: WsEventName, callback: (payload: any) => void) {
    if (!echoInstance || !authStore.user) return
    const channelName = `private-user.${authStore.user.id}`
    if (!subscribedChannels.value.includes(channelName)) {
      echoInstance.private(channelName)
      subscribedChannels.value.push(channelName)
    }
    echoInstance.listen(event, callback)
  }

  function subscribeToProjectChannel(projectId: number, event: WsEventName, callback: (payload: any) => void) {
    if (!echoInstance) return
    const channelName = `private-project.${projectId}`
    if (!subscribedChannels.value.includes(channelName)) {
      echoInstance.private(channelName)
      subscribedChannels.value.push(channelName)
    }
    echoInstance.listen(event, callback)
  }

  function disconnect() {
    if (echoInstance) {
      echoInstance.disconnect()
      echoInstance = null
      isConnected.value = false
      subscribedChannels.value = []
    }
  }

  return { isConnected, subscribedChannels, init, subscribeToUserChannel, subscribeToProjectChannel, disconnect }
}

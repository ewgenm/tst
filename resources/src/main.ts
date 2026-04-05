// ============================================================
// Main entry point — Vue 3 + TypeScript
// ТЗ №2 v1.1 — Этап 1
// ============================================================

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import '../css/app.css'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')

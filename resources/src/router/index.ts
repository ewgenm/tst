// ============================================================
// Vue Router — маршруты (ТЗ №2 v1.1 — раздел 6.1)
// ============================================================

import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes: RouteRecordRaw[] = [
  { path: '/login', name: 'Login', component: () => import('@/pages/auth/LoginPage.vue'), meta: { requiresGuest: true } },
  { path: '/register', name: 'Register', component: () => import('@/pages/auth/RegisterPage.vue'), meta: { requiresGuest: true } },
  {
    path: '/', component: () => import('@/layouts/AppLayout.vue'), meta: { requiresAuth: true },
    children: [
      { path: '', name: 'Inbox', component: () => import('@/pages/tasks/InboxPage.vue') },
      { path: 'today', name: 'Today', component: () => import('@/pages/tasks/TodayPage.vue') },
      { path: 'projects', name: 'Projects', component: () => import('@/pages/projects/ProjectsPage.vue') },
      { path: 'projects/:id', name: 'ProjectDetail', component: () => import('@/pages/projects/ProjectDetailPage.vue') },
      { path: 'tasks/:id', name: 'TaskDetail', component: () => import('@/pages/tasks/TaskDetailPage.vue') },
      { path: 'habits', name: 'Habits', component: () => import('@/pages/habits/HabitsPage.vue') },
      { path: 'search', name: 'Search', component: () => import('@/pages/search/SearchPage.vue') },
      {
        path: 'settings', name: 'Settings', component: () => import('@/pages/settings/SettingsPage.vue'),
        children: [
          { path: '', redirect: '/settings/profile' },
          { path: 'profile', component: () => import('@/pages/settings/ProfileSettings.vue') },
          { path: 'integrations', component: () => import('@/pages/settings/IntegrationsSettings.vue') },
        ],
      },
    ],
  },
]

const router = createRouter({ history: createWebHistory(), routes })

router.beforeEach(async (to, _from, next) => {
  const authStore = useAuthStore()
  if (!authStore.user) await authStore.fetchMe()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) next('/login')
  else if (to.meta.requiresGuest && authStore.isAuthenticated) next('/')
  else next()
})

export default router

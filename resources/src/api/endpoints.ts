// ============================================================
// API Endpoints (синхрон с ТЗ №0 v1.4 + HOTFIX)
// ============================================================

export const endpoints = {
  // Auth & Profile
  auth: {
    register: '/auth/register',
    login: '/auth/login',
    logout: '/auth/logout',
    me: '/auth/me',
  },

  // Dashboard & Search
  dashboard: {
    stats: '/dashboard/stats',
  },
  search: '/search',
  health: '/health',

  // Tasks
  tasks: '/tasks',
  task: (id: number) => `/tasks/${id}`,
  taskComplete: (id: number) => `/tasks/${id}/complete`,
  taskPosition: (id: number) => `/tasks/${id}/position`,
  taskRestore: (id: number) => `/tasks/${id}/restore`,

  // Task Relations
  taskTags: (taskId: number, tagId: number) => `/tasks/${taskId}/tags/${tagId}`,
  taskAttachments: (taskId: number) => `/tasks/${taskId}/attachments`,

  // Comments
  taskComments: (taskId: number) => `/tasks/${taskId}/comments`,
  comment: (id: number) => `/comments/${id}`,
  commentAttachments: (commentId: number) => `/comments/${commentId}/attachments`,

  // Projects
  projects: '/projects',
  project: (id: number) => `/projects/${id}`,
  projectRestore: (id: number) => `/projects/${id}/restore`,
  projectArchive: (id: number) => `/projects/${id}/archive`,
  projectMembers: (projectId: number) => `/projects/${projectId}/members`,
  projectInvite: (projectId: number) => `/projects/${projectId}/invite`,
  projectLeave: (projectId: number) => `/projects/${projectId}/leave`,
  projectExport: (projectId: number) => `/projects/${projectId}/export`,

  // Project Members
  projectMemberAccept: (id: number) => `/project-members/${id}/accept`,
  projectMemberDelete: (id: number) => `/project-members/${id}`,

  // Habits
  habits: '/habits',
  habit: (id: number) => `/habits/${id}`,
  habitLog: (id: number) => `/habits/${id}/log`,
  habitStats: (id: number) => `/habits/${id}/stats`,

  // Tags
  tags: '/tags',
  tag: (id: number) => `/tags/${id}`,

  // Notifications
  notifications: '/notifications',
  notificationRead: (id: number) => `/notifications/${id}/read`,
  notificationsReadAll: '/notifications/read-all',
  notificationsUnreadCount: '/notifications/unread-count',

  // Attachments
  attachment: (id: number) => `/attachments/${id}`,

  // Integrations & AI
  integrations: '/integrations',
  integrationConnect: (provider: string) => `/integrations/${provider}/connect`,
  integrationDisconnect: (provider: string) => `/integrations/${provider}/disconnect`,
  aiGenerateSubtasks: '/ai/generate-subtasks',
  aiSuggestPlan: '/ai/suggest-plan',

  // Import
  import: '/import',
} as const;

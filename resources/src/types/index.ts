// ============================================================
// ТЗ №0 v1.4 + HOTFIX — Все типы данных (синхрон с API Contract)
// ============================================================

// 2.1. User
export interface User {
  id: number;
  name: string;
  email: string;
  avatar_url: string | null;
  email_verified_at: string | null;
  timezone: string; // "Europe/Moscow"
  locale: string; // "ru"
  theme: 'light' | 'dark' | 'system';
  created_at: string;
}

// 2.2. Project
export interface Project {
  id: number;
  owner_id: number;
  name: string;
  description: string | null;
  color: string; // Hex
  icon: string | null;
  is_favorite: boolean;
  is_archived: boolean;
  sort_order: number;
  tasks_count?: number;
  owner?: User;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
}

// 2.3. Task
export interface Task {
  id: number;
  project_id: number | null;
  parent_task_id: number | null;
  title: string;
  description: string | null;
  status: 'todo' | 'in_progress' | 'review' | 'done';
  priority: 'low' | 'medium' | 'high' | 'urgent';
  due_at: string | null; // UTC ISO 8601
  position: number;
  assignee_id: number | null;
  created_by: number;
  is_recurring: boolean;
  recurring_rule: string | null;

  // Computed aggregates
  comments_count?: number;
  attachments_count?: number;
  subtasks_completed?: number;
  subtasks_total?: number;

  // Relations
  project?: Project;
  assignee?: User;
  tags?: Tag[];
  attachments?: Attachment[];
  subtasks?: Task[];

  created_at: string;
  updated_at: string;
  deleted_at: string | null;
}

// Task Filters
export interface TaskFilters {
  project_id?: number | null;
  status?: string;
  assignee_id?: number;
  priority?: string;
  due_at_from?: string;
  due_at_to?: string;
  search?: string;
  page?: number;
  per_page?: number;
  sort?: string;
  order?: 'asc' | 'desc';
  archived?: boolean;
}

// 2.4. Comment
export interface Comment {
  id: number;
  task_id: number;
  user_id: number;
  parent_comment_id: number | null;
  content: string; // Markdown
  attachments?: Attachment[];
  user?: User;
  replies_count?: number;
  created_at: string;
  updated_at: string;
}

// 2.5. Tag
export interface Tag {
  id: number;
  user_id: number;
  project_id: number | null;
  name: string;
  color: string;
  created_at: string;
  updated_at: string;
}

// 2.6. Attachment (Polymorphic)
export interface Attachment {
  id: number;
  attachable_type: 'App\\Models\\Task' | 'App\\Models\\Comment';
  attachable_id: number;
  user_id: number;
  filename: string;
  url: string;
  mime_type: string;
  size: number;
  created_at: string;
}

// 2.7. Habit
export interface Habit {
  id: number;
  user_id: number;
  name: string;
  color: string;
  icon: string | null;
  frequency: 'daily' | 'weekly' | 'custom';
  target_days: number[]; // [0,1,2,3,4,5,6] 0=Sunday
  current_streak: number;
  best_streak: number;
  last_completed_at: string | null;
  completions_count?: number;
  created_at: string;
  updated_at: string;
}

// 2.8. Notification
export interface Notification {
  id: number;
  user_id: number;
  type: 'task_assigned' | 'task_due_soon' | 'comment_added' | 'project_invite';
  title: string;
  message: string;
  data: Record<string, any>;
  is_read: boolean;
  read_at: string | null;
  created_at: string;
}

// 2.9. ProjectMember
export interface ProjectMember {
  id: number;
  project_id: number;
  user_id: number;
  role: 'admin' | 'member' | 'viewer';
  status: 'pending' | 'active';
  invited_by: number | null;
  invited_at?: string;
  user?: User;
  created_at: string;
  updated_at: string;
}

// Search & Dashboard
export interface SearchResult {
  tasks: Task[];
  projects: Project[];
  habits: Habit[];
}

export interface DashboardStats {
  tasks_by_status: Record<string, number>;
  overdue_tasks: number;
  habits_streak: Habit[];
}

// API Response Wrappers
export interface ApiSuccess<T> {
  success: true;
  data: T;
  meta?: { timestamp: string };
  pagination?: PaginationMeta;
}

export interface PaginationMeta {
  current_page: number;
  per_page: number;
  total: number;
  total_pages: number;
  has_more: boolean;
}

export interface ApiError {
  success: false;
  error: {
    code: string;
    message: string;
    details?: Record<string, string[]>;
  };
}

export type ApiResponse<T> = ApiSuccess<T> | ApiError;

// WebSocket Events
export interface WsEventTaskCreated {
  task: Task;
  project_id: number;
}

export interface WsEventTaskUpdated {
  task: Task;
  changes: string[];
  project_id: number;
}

export interface WsEventTaskDeleted {
  task_id: number;
  project_id: number;
}

export interface WsEventTaskAssigned {
  task: Task;
  assignee_id: number;
  assigned_by: number;
}

export interface WsEventCommentCreated {
  comment: Comment;
  task_id: number;
  project_id: number;
}

export interface WsEventProjectMemberAdded {
  user: User;
  project_id: number;
  role: string;
}

export interface WsEventNotificationNew {
  notification: Notification;
}

// Integrations
export interface Integration {
  provider: string;
  name: string;
  connected: boolean;
  connected_at: string | null;
}

// Habit Completion
export interface HabitCompletion {
  id: number;
  habit_id: number;
  completed_date: string;
  created_at: string;
  updated_at: string;
}

// Constants
export const TASK_STATUS = {
  TODO: 'todo',
  IN_PROGRESS: 'in_progress',
  REVIEW: 'review',
  DONE: 'done',
} as const;

export const TASK_PRIORITY = {
  LOW: 'low',
  MEDIUM: 'medium',
  HIGH: 'high',
  URGENT: 'urgent',
} as const;

export const PROJECT_MEMBER_ROLE = {
  ADMIN: 'admin',
  MEMBER: 'member',
  VIEWER: 'viewer',
} as const;

export const HABIT_FREQUENCY = {
  DAILY: 'daily',
  WEEKLY: 'weekly',
  CUSTOM: 'custom',
} as const;

export const NOTIFICATION_TYPE = {
  TASK_ASSIGNED: 'task_assigned',
  TASK_DUE_SOON: 'task_due_soon',
  COMMENT_ADDED: 'comment_added',
  PROJECT_INVITE: 'project_invite',
} as const;

export const USER_THEME = {
  LIGHT: 'light',
  DARK: 'dark',
  SYSTEM: 'system',
} as const;

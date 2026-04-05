# 📘 TaskSync Team — Frontend Documentation

**Проект:** TaskSync Team Web Client
**Стек:** Vue 3.4 + TypeScript + Pinia + Tailwind CSS
**Версия:** 1.0
**Дата:** 04.04.2026
**Локальный домен:** `http://tst.test`

---

## 📋 Содержание

1. [Обзор проекта](#обзор-проекта)
2. [Архитектура](#архитектура)
3. [Установка и запуск](#установка-и-запуск)
4. [Структура файлов](#структура-файлов)
5. [Stores (State Management)](#stores-state-management)
6. [Компоненты](#компоненты)
7. [Страницы](#страницы)
8. [Composables](#composables)
9. [API Integration](#api-integration)
10. [WebSocket Realtime](#websocket-realtime)
11. [Тестирование](#тестирование)
12. [Развёртывание](#развёртывание)
13. [Гайдлайны разработки](#гайдлайны-разработки)

---

## Обзор проекта

TaskSync Team — это веб-приложение для управления задачами, проектами и привычками.
Фронтенд построен на Vue 3 с использованием Composition API, TypeScript для типобезопасности
и Pinia для управления состоянием.

### Ключевые возможности

| Возможность | Описание |
|-------------|----------|
| **Управление задачами** | CRUD, drag & drop сортировка, подзадачи, recurring tasks |
| **Проекты** | CRUD, приглашения участников, роли (admin/member/viewer), архивирование |
| **Привычки** | Трекер привычек с серий streak и календарём |
| **Realtime** | WebSocket через Laravel Echo + Reverb |
| **Тёмная тема** | Переключение light/dark/system |
| **Поиск** | Полнотекстовый поиск по задачам, проектам, привычкам |
| **Уведомления** | Toast уведомления + realtime notifications |

### Зависимости

```json
{
  "core": ["vue", "vue-router", "pinia", "typescript"],
  "ui": ["tailwindcss", "radix-vue", "@radix-icons/vue"],
  "api": ["axios", "laravel-echo", "pusher-js"],
  "utils": ["date-fns", "date-fns-tz", "@vueuse/core", "uuid"],
  "forms": ["vee-validate", "zod"],
  "dnd": ["vuedraggable"],
  "testing": ["vitest", "@vue/test-utils", "jsdom", "@pinia/testing"]
}
```

---

## Архитектура

### Слои приложения

```
┌─────────────────────────────────────────────────┐
│                   Pages (Views)                  │
│  InboxPage, TodayPage, ProjectsPage, etc.       │
├─────────────────────────────────────────────────┤
│              Feature Components                  │
│  TaskList, TaskForm, CommentList, etc.          │
├─────────────────────────────────────────────────┤
│              Base UI Components                  │
│  Button, Input, Modal (Radix Vue)               │
├─────────────────────────────────────────────────┤
│              Composables                         │
│  useDateFormatter, useWebSocket, useToast       │
├─────────────────────────────────────────────────┤
│              Pinia Stores                        │
│  auth, tasks, projects, habits, etc.            │
├─────────────────────────────────────────────────┤
│              API Layer                           │
│  Axios client + interceptors + endpoints        │
└─────────────────────────────────────────────────┘
```

### Принципы

- **Composition API** — только `<script setup lang="ts">`
- **Type-Safe** — все props, emits, stores строго типизированы
- **Optimistic Updates** — UI обновляется сразу, при ошибке — откат + toast
- **Headless UI** — Radix Vue для доступности + кастомные Tailwind стили
- **Error Boundaries** — глобальная обработка ошибок API с toast

---

## Установка и запуск

### Предварительные требования

- Node.js 18+
- npm 9+
- Laravel Herd (для локального бэкенда)
- Домен `http://tst.test` должен быть настроен

### Установка

```bash
# Установка зависимостей
npm install

# Копирование .env
cp .env.example .env
```

### Запуск dev сервера

```bash
npm run dev
```

Сервер запустится на `http://localhost:5173` с прокси на `http://tst.test`

### Production build

```bash
npm run build
# Output: public/build/
```

### Проверка типов

```bash
npx tsc --noEmit
```

---

## Структура файлов

```
resources/src/
├── api/
│   ├── client.ts              # Axios instance с интерцепторами
│   └── endpoints.ts           # Все API эндпоинты (typed)
├── assets/
│   ├── styles/                # Глобальные стили
│   └── images/                # Иконки, логотипы
├── components/
│   ├── ui/                    # Базовые UI компоненты
│   ├── layout/                # Layout компоненты
│   │   ├── Sidebar.vue        # Боковая навигация
│   │   └── Header.vue         # Шапка с поиском и уведомлениями
│   └── features/              # Функциональные компоненты
│       ├── TaskItem.vue       # Карточка задачи
│       ├── TaskList.vue       # Drag & Drop список задач
│       ├── TaskForm.vue       # Форма создания/редактирования задачи
│       ├── ProjectCard.vue    # Карточка проекта
│       ├── CommentList.vue    # Комментарии с ответами
│       ├── AttachmentUploader.vue  # Загрузка файлов
│       ├── SubtaskList.vue    # Список подзадач
│       ├── SearchBar.vue      # Поиск с debounce
│       └── NotificationBell.vue  # Уведомления
├── composables/
│   ├── useToast.ts            # Toast уведомления
│   ├── useDateFormatter.ts    # Форматирование дат с timezone
│   ├── useWebSocket.ts        # Laravel Echo integration
│   └── useRealtime.ts         # Обёртка для realtime событий
├── layouts/
│   └── AppLayout.vue          # Главный layout
├── pages/
│   ├── auth/
│   │   ├── LoginPage.vue      # Страница входа
│   │   └── RegisterPage.vue   # Страница регистрации
│   ├── tasks/
│   │   ├── InboxPage.vue      # Входящие задачи
│   │   ├── TodayPage.vue      # Задачи на сегодня
│   │   └── TaskDetailPage.vue # Детали задачи
│   ├── projects/
│   │   ├── ProjectsPage.vue       # Список проектов
│   │   └── ProjectDetailPage.vue  # Детали проекта
│   ├── habits/
│   │   └── HabitsPage.vue     # Трекер привычек
│   ├── search/
│   │   └── SearchPage.vue     # Результаты поиска
│   └── settings/
│       ├── SettingsPage.vue       # Настройки
│       ├── ProfileSettings.vue    # Профиль
│       └── IntegrationsSettings.vue  # Интеграции
├── stores/
│   ├── auth.ts                # Аутентификация
│   ├── tasks.ts               # Задачи (CRUD + WebSocket)
│   ├── projects.ts            # Проекты + Members
│   ├── habits.ts              # Привычки
│   ├── notifications.ts       # Уведомления
│   ├── comments.ts            # Комментарии
│   ├── search.ts              # Поиск
│   └── ui.ts                  # UI состояние (тема, sidebar, toast)
├── types/
│   └── index.ts               # TypeScript интерфейсы (20+)
├── router/
│   └── index.ts               # Vue Router + auth guards
├── test/
│   └── setup.ts               # Vitest setup
├── App.vue                    # Root компонент
└── main.ts                    # Entry point
```

---

## Stores (State Management)

### Auth Store

**Файл:** `stores/auth.ts`

| Метод | Описание |
|-------|----------|
| `fetchMe()` | Загрузить текущего пользователя |
| `login(email, password)` | Войти |
| `register(name, email, password)` | Зарегистрироваться |
| `logout()` | Выйти |
| `updateProfile(payload)` | Обновить профиль |

**Computed:**
- `isAuthenticated` — авторизован ли пользователь
- `isEmailVerified` — подтверждён ли email
- `userTimezone` — часовой пояс пользователя
- `userLocale` — язык пользователя

### Tasks Store

**Файл:** `stores/tasks.ts`

| Метод | Описание |
|-------|----------|
| `fetchTasks(filters)` | Загрузить задачи с фильтрами |
| `createTask(payload)` | Создать задачу (optimistic) |
| `updateTask(id, payload)` | Обновить задачу (optimistic) |
| `deleteTask(id)` | Удалить задачу (optimistic) |
| `completeTask(id)` | Завершить задачу (recurring logic) |
| `reorderTask(id, position)` | Изменить порядок |
| `setupRealtime(userId, projectId)` | Подписка на WebSocket события |

**Computed:**
- `inboxTasks` — задачи без проекта
- `todayTasks` — задачи на сегодня
- `overdueTasks` — просроченные задачи

### Projects Store

**Файл:** `stores/projects.ts`

| Метод | Описание |
|-------|----------|
| `fetchProjects(archived)` | Загрузить проекты |
| `createProject(payload)` | Создать проект |
| `updateProject(id, payload)` | Обновить проект |
| `deleteProject(id)` | Удалить проект |
| `archiveProject(id)` | Архивировать |
| `toggleFavorite(id)` | Избранное |
| `fetchMembers(projectId)` | Загрузить участников |
| `inviteMember(projectId, email, role)` | Пригласить |
| `removeMember(projectId, memberId)` | Удалить участника |
| `leaveProject(projectId)` | Покинуть проект |
| `setupProjectRealtime(projectId)` | WebSocket для проекта |

### Habits Store

**Файл:** `stores/habits.ts`

| Метод | Описание |
|-------|----------|
| `fetchHabits()` | Загрузить привычки |
| `createHabit(payload)` | Создать привычку |
| `updateHabit(id, payload)` | Обновить привычку |
| `deleteHabit(id)` | Удалить привычку |
| `logCompletion(habitId, date)` | Отметить выполнение |
| `fetchHabitStats(habitId)` | Статистика привычки |

### Notifications Store

**Файл:** `stores/notifications.ts`

| Метод | Описание |
|-------|----------|
| `fetchNotifications()` | Загрузить уведомления |
| `markAsRead(id)` | Прочитать |
| `markAllAsRead()` | Прочитать все |
| `fetchUnreadCount()` | Счётчик непрочитанных |
| `setupRealtime()` | WebSocket для уведомлений |

### UI Store

**Файл:** `stores/ui.ts`

| Метод | Описание |
|-------|----------|
| `toggleSidebar()` | Свернуть/развернуть меню |
| `openModal(name)` | Открыть модальное окно |
| `closeModal()` | Закрыть модальное окно |
| `setTheme(theme)` | Установить тему |
| `showToast(message, type)` | Показать toast |

---

## Компоненты

### TaskItem

**Файл:** `components/features/TaskItem.vue`

**Props:**
- `task: Task` — объект задачи
- `showProject?: boolean` — показывать проект
- `compact?: boolean` — компактный режим

**Events:**
- `toggle(id)` — изменение статуса
- `edit(id)` — редактирование
- `delete(id)` — удаление

### TaskList

**Файл:** `components/features/TaskList.vue`

**Features:**
- Drag & Drop сортировка (vuedraggable)
- Skeleton loaders
- Empty state
- Оптимистичные обновления

### TaskForm

**Файл:** `components/features/TaskForm.vue`

**Props:**
- `defaultData?: Partial<Task>` — данные для редактирования
- `projectId?: number | null` — ID проекта

**Events:**
- `created` — задача создана
- `updated` — задача обновлена
- `cancel` — отмена

**Features:**
- Все поля задачи (title, description, priority, status, due_at)
- Recurring task support (CRITICAL FIX #10)
- Validation errors отображение

### CommentList

**Файл:** `components/features/CommentList.vue`

**Features:**
- Древовидные комментарии
- Ответы на комментарии
- Inline редактирование
- Удаление с подтверждением

### AttachmentUploader

**Файл:** `components/features/AttachmentUploader.vue`

**Features:**
- Drag & Drop загрузка
- Валидация типов и размера (10MB)
- Превью файлов
- Удаление вложений

### SubtaskList

**Файл:** `components/features/SubtaskList.vue`

**Features:**
- Прогресс-бар выполнения
- Чекбоксы подзадач
- Inline добавление

### SearchBar

**Файл:** `components/features/SearchBar.vue`

**Features:**
- Debounce 300ms
- Keyboard shortcut `/`
- Индикатор загрузки

### NotificationBell

**Файл:** `components/features/NotificationBell.vue`

**Features:**
- Badge непрочитанных
- Dropdown с уведомлениями
- Типы уведомлений с иконками
- Mark all read

---

## Страницы

### InboxPage

**Путь:** `/`

Отображает задачи без проекта (`project_id IS NULL`).

### TodayPage

**Путь:** `/today`

Задачи на сегодня + просроченные задачи.

### ProjectsPage

**Путь:** `/projects`

Список проектов с вкладками: Активные, Избранные, Архив.

### ProjectDetailPage

**Путь:** `/projects/:id`

Вкладки:
- Задачи проекта
- Участники (приглашения, роли)
- Настройки (для владельца)

### TaskDetailPage

**Путь:** `/tasks/:id`

Полная информация о задаче:
- Описание
- Подзадачи
- Комментарии
- Вложения
- Мета-информация

### HabitsPage

**Путь:** `/habits`

Трекер привычек с:
- Созданием привычек
- Отметкой выполнения
- Недельным календарём
- Streak статистикой

### SearchPage

**Путь:** `/search?q=...`

Результаты поиска по задачам, проектам, привычкам.

### Settings

**Пути:** `/settings/profile`, `/settings/integrations`

- Профиль: имя, email, timezone, тема
- Интеграции: Telegram, Google, GitHub

---

## Composables

### useDateFormatter

**Файл:** `composables/useDateFormatter.ts`

```typescript
const { formatDueDate, formatDueRelative, isOverdue, localToUTC } = useDateFormatter()

formatDueDate('2026-04-10T18:00:00Z', 'dd MMM yyyy') // "10 апр 2026"
formatDueRelative('2026-04-10T18:00:00Z')            // "через 5 дней"
isOverdue('2026-04-01T18:00:00Z')                    // true/false
```

### useWebSocket

**Файл:** `composables/useWebSocket.ts`

```typescript
const ws = useWebSocket()

ws.init()
ws.subscribeToUserChannel('task.created', callback)
ws.subscribeToProjectChannel(projectId, 'task.updated', callback)
ws.disconnect()
```

### useRealtime

**Файл:** `composables/useRealtime.ts`

```typescript
const realtime = useRealtime()

realtime.subscribeToUserEvents({
  onTaskCreated: (payload) => { ... },
  onTaskUpdated: (payload) => { ... },
  onNotificationNew: (payload) => { ... },
})
```

### useToast

**Файл:** `composables/useToast.ts`

```typescript
const { show, hide } = useToast()

show('Задача создана', 'success')
show('Ошибка сети', 'error')
```

---

## API Integration

### Client

**Файл:** `api/client.ts`

Axios instance с:
- CSRF token interceptor (Sanctum)
- Error handling (401, 403, 422, 429, 5xx)
- Rate limiting retry logic (429 → retry after delay)
- Field-level validation errors propagation

### Endpoints

**Файл:** `api/endpoints.ts`

Все эндпоинты типизированы:

```typescript
endpoints.tasks                    // GET /tasks
endpoints.task(id)                 // GET/PUT/DELETE /tasks/{id}
endpoints.taskComplete(id)         // POST /tasks/{id}/complete
endpoints.projects                 // GET/POST /projects
endpoints.project(id)              // GET/PUT/DELETE /projects/{id}
// ... и т.д.
```

### Rate Limiting

| Endpoint Group | Limit | Window |
|----------------|-------|--------|
| General API | 100 req | 1 min |
| File Uploads | 10 uploads | 1 min |
| Auth | 5 attempts | 1 min |
| Search | 30 req | 1 min |

---

## WebSocket Realtime

### Каналы

| Канал | События |
|-------|---------|
| `private-user.{userId}` | task.created, task.updated, task.deleted, notification.new |
| `private-project.{projectId}` | task.created, task.updated, comment.created, project.member.added |

### Инициализация

1. **App.vue** — инициализация Echo после авторизации
2. **Tasks Store** — подписка на `private-user.{userId}`
3. **AppLayout** — подписка на `private-project.{id}` при навигации

### UI Effects

- Toast уведомления при новых событиях
- Автоматическое обновление списков задач
- Счётчик непрочитанных уведомлений

---

## Тестирование

### Запуск тестов

```bash
# Все тесты
npx vitest run

# Watch mode
npx vitest

# Конкретный файл
npx vitest run TaskItem.test.ts

# Coverage
npx vitest run --coverage
```

### Структура тестов

```
resources/src/
├── components/features/__tests__/
│   └── TaskItem.test.ts          # 18 тестов
├── stores/__tests__/
│   ├── tasks.test.ts             # 12 тестов
│   └── auth.test.ts              # 15 тестов
├── composables/__tests__/
│   └── useDateFormatter.test.ts  # 13 тестов
└── test/
    └── setup.ts                  # Vitest setup
```

### Покрытие

| Компонент | Тестов | Что проверяет |
|-----------|--------|---------------|
| TaskItem | 18 | Рендеринг, статусы, приоритеты, события, a11y |
| Tasks Store | 12 | CRUD, optimistic updates, rollback, computed |
| Auth Store | 15 | Login, register, logout, fetchMe, computed |
| useDateFormatter | 13 | Форматирование, timezones, isOverdue |

**Итого: 58 тестов** ✅

---

## Развёртывание

### Development

```bash
npm run dev
# → http://localhost:5173 (proxy → http://tst.test)
```

### Production

```bash
npm run build
# → public/build/
```

Laravel Vite plugin автоматически подключает собранные ассеты.

### PWA

Service Worker настроен через `vite-plugin-pwa`:
- Кэширование статических файлов
- Offline доступ
- Push уведомления (в будущем)

---

## Гайдлайны разработки

### Стиль кода

- **Vue:** `<script setup lang="ts">` только, никаких Options API
- **TypeScript:** strict mode, все props/emits/stores типизированы
- **CSS:** Tailwind utility classes, никаких inline styles
- **Именование:** camelCase для переменных, PascalCase для компонентов

### API запросы

```typescript
// ✅ Правильно
const { data } = await apiClient.get('/tasks', { params: filters })

// ❌ Неправильно — не используйте fetch напрямую
const response = await fetch('/api/v1/tasks')
```

### Optimistic Updates

```typescript
async function updateTask(id: number, payload: Partial<Task>) {
  const original = tasks.value.find(t => t.id === id)
  const backup = { ...original }

  // Optimistic update
  Object.assign(original, payload)

  try {
    const { data } = await apiClient.put(`/tasks/${id}`, payload)
    Object.assign(original, data.data)
  } catch {
    // Rollback
    Object.assign(original, backup)
    showToast('Не удалось обновить', 'error')
  }
}
```

### Коммиты

```
feat: add task drag & drop
fix: timezone conversion for due dates
style: update button hover states
refactor: extract TaskItem component
test: add Tasks Store tests
docs: update API documentation
```

---

## Чек-лист завершения

- [x] Все модули реализованы согласно ТЗ №0
- [x] Типы TypeScript синхронизированы с API контрактом
- [x] Тёмная/светлая тема работает корректно
- [x] WebSocket подключения работают
- [x] Optimistic updates с откатами реализованы
- [x] Обработка ошибок API с field-level валидацией
- [x] Rate limiting retry logic
- [x] Часовые пояса конвертируются
- [x] Поиск реализован
- [x] Recurring task поля в TaskForm
- [x] Responsive дизайн
- [x] Критические компоненты покрыты тестами
- [x] Production build проходит без ошибок
- [x] PWA Service Worker настроен

---

## Ссылки

- [TZ №0 — API Contract](./TZ_0_v1_4+HF.txt)
- [TZ №1 — Backend](./TZ_1_v1_1.txt)
- [TZ №2 — Frontend](./TZ_2_v1_1_part1.txt)
- [API Documentation](./API_DOCUMENTATION.md)

# TaskSync Team — API Documentation

**Версия:** v1.0 (Production)
**Base URL:** `http://tst.test/api/v1` (Local) | `https://api.tasksync.team/api/v1` (Production)
**Аутентификация:** Laravel Sanctum (SPA cookies)
**Дата:** Апрель 2026

---

## 📋 Содержание

1. [Общие правила](#общие-правила)
2. [Auth & Profile](#1-auth--profile)
3. [Dashboard & Search](#2-dashboard--search)
4. [Tasks](#3-tasks)
5. [Projects](#4-projects)
6. [Comments](#5-comments)
7. [Tags](#6-tags)
8. [Attachments](#7-attachments)
9. [Habits](#8-habits)
10. [Notifications](#9-notifications)
11. [AI Endpoints](#10-ai-endpoints)
12. [Export/Import](#11-exportimport)
13. [Health Check](#12-health-check)
14. [Ошибки и коды](#ошибки-и-коды)
15. [Rate Limiting](#rate-limiting)

---

## Общие правила

### Формат запроса
- **Content-Type:** `application/json`
- **Accept:** `application/json`
- **CSRF:** `X-XSRF-TOKEN` header (для SPA)

### Формат ответа (успех)
```json
{
  "success": true,
  "data": { ... },
  "meta": { "timestamp": "2026-04-04T10:00:00Z" }
}
```

### Формат ответа (пагинация)
```json
{
  "success": true,
  "data": [ ... ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "total_pages": 8,
    "has_more": true
  },
  "meta": { "timestamp": "2026-04-04T10:00:00Z" }
}
```

### Формат ответа (ошибка)
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Описание ошибки"
  }
}
```

### Параметры запроса
| Параметр | Тип | Описание |
|----------|-----|----------|
| `?page` | int | Номер страницы (default: 1) |
| `?per_page` | int | Записей на странице (default: 20) |
| `?sort` | string | Поле сортировки |
| `?order` | string | Порядок: `asc` или `desc` |
| `?include` | string | Включить связи: `project,assignee,tags` |

---

## 1. Auth & Profile

### Регистрация
```
POST /auth/register
```

**Body:**
```json
{
  "name": "Имя пользователя",
  "email": "user@example.com",
  "password": "securepassword",
  "password_confirmation": "securepassword",
  "timezone": "Europe/Moscow",
  "locale": "ru",
  "theme": "light"
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "user": { "id": 1, "name": "...", "email": "..." },
    "message": "Регистрация успешна. Подтвердите email."
  }
}
```

### Вход
```
POST /auth/login
```

**Body:**
```json
{
  "email": "user@example.com",
  "password": "securepassword"
}
```

### Выход
```
POST /auth/logout
```

### Получить профиль
```
GET /auth/me
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "User",
    "email": "user@example.com",
    "avatar_url": null,
    "timezone": "Europe/Moscow",
    "locale": "ru",
    "theme": "light",
    "email_verified_at": "2026-04-04T10:00:00Z",
    "created_at": "2026-04-04T10:00:00Z"
  }
}
```

### Обновить профиль
```
PUT /auth/me
```

**Body:** (любые поля опциональны)
```json
{
  "name": "Новое имя",
  "theme": "dark",
  "timezone": "America/New_York"
}
```

---

## 2. Dashboard & Search

### Статистика
```
GET /dashboard/stats
```

**Response:**
```json
{
  "success": true,
  "data": {
    "tasks_by_status": { "todo": 5, "in_progress": 3, "done": 10 },
    "overdue_tasks": 2,
    "habits_streaks": [
      { "name": "Exercise", "current_streak": 7 }
    ],
    "projects_count": 3
  }
}
```

### Поиск
```
GET /search?q=keyword&types=tasks,projects,habits
```

**Response:**
```json
{
  "success": true,
  "data": {
    "tasks": [
      { "id": 1, "title": "Find this task", "type": "task", "status": "todo" }
    ],
    "projects": [
      { "id": 1, "name": "My Project", "type": "project" }
    ],
    "habits": [
      { "id": 1, "name": "Exercise", "type": "habit" }
    ]
  }
}
```

**Параметры:**
| Параметр | Описание |
|----------|----------|
| `q` | Поисковый запрос (обязательный) |
| `types` | Типы: `tasks`, `projects`, `habits` (через запятую) |

---

## 3. Tasks

### Список задач
```
GET /tasks
```

**Фильтры:**
| Параметр | Пример | Описание |
|----------|--------|----------|
| `filter[project_id]` | `15` | ID проекта |
| `filter[status]` | `todo,in_progress` | Статусы |
| `filter[assignee_id]` | `3` | Исполнитель |
| `filter[priority]` | `high,urgent` | Приоритеты |
| `sort` | `due_at` | Сортировка |

### Создать задачу
```
POST /tasks
```

**Body:**
```json
{
  "title": "Новая задача",
  "description": "Описание задачи",
  "project_id": 15,
  "status": "todo",
  "priority": "medium",
  "due_at": "2026-04-10T18:00:00Z",
  "assignee_id": 3,
  "is_recurring": false,
  "recurring_rule": null
}
```

**Status enum:** `todo`, `in_progress`, `review`, `done`
**Priority enum:** `low`, `medium`, `high`, `urgent`

### Получить задачу
```
GET /tasks/{id}?include=project,assignee,tags,attachments,subtasks
```

### Обновить задачу
```
PUT /tasks/{id}
```

### Удалить задачу
```
DELETE /tasks/{id}
```

### Восстановить задачу
```
POST /tasks/{id}/restore
```

### Завершить задачу
```
POST /tasks/{id}/complete
```
> Если `is_recurring = true`, создаётся копия задачи с новым `due_at`

### Изменить позицию
```
PUT /tasks/{id}/position
```

**Body:**
```json
{ "position": 5 }
// ИЛИ
{ "after_id": 123 }
```

---

## 4. Projects

### Список проектов
```
GET /projects?archived=false&sort=sort_order
```

### Создать проект
```
POST /projects
```

**Body:**
```json
{
  "name": "Мой проект",
  "description": "Описание проекта",
  "color": "#3B82F6",
  "icon": "📁"
}
```

### Получить проект
```
GET /projects/{id}
```

### Обновить проект
```
PUT /projects/{id}
```

### Удалить проект
```
DELETE /projects/{id}
```
> Только владелец может удалить проект

### Архивировать
```
POST /projects/{id}/archive
```

### Восстановить
```
POST /projects/{id}/restore
```

### Покинуть проект
```
DELETE /projects/{id}/leave
```

### Пригласить участника
```
POST /projects/{id}/invite
```

**Body:**
```json
{
  "email": "newuser@example.com",
  "role": "member"
}
```

**Role enum:** `admin`, `member`, `viewer`

### Участники проекта
```
GET /projects/{id}/members
```

### Принять приглашение
```
PATCH /project-members/{id}/accept
```

### Удалить участника
```
DELETE /project-members/{id}
```

---

## 5. Comments

### Список комментариев задачи
```
GET /tasks/{id}/comments
```

### Создать комментарий
```
POST /tasks/{id}/comments
```

**Body:**
```json
{
  "content": "Текст комментария (Markdown)",
  "parent_comment_id": 42
}
```
> `parent_comment_id` — для ответов на комментарий

### Получить комментарий
```
GET /comments/{id}?include=attachments,replies
```

### Обновить комментарий
```
PUT /comments/{id}
```

### Удалить комментарий
```
DELETE /comments/{id}
```

---

## 6. Tags

### Список тегов
```
GET /tags?project_id=15
```

### Создать тег
```
POST /tags
```

**Body:**
```json
{
  "name": "urgent",
  "color": "#FF0000",
  "project_id": 15
}
```
> `project_id: null` = глобальный личный тег

### Обновить тег
```
PUT /tags/{id}
```

### Удалить тег
```
DELETE /tags/{id}
```

---

## 7. Attachments

### Загрузить вложение для задачи
```
POST /tasks/{id}/attachments
```

**Content-Type:** `multipart/form-data`

**Body:**
```
file: [binary]
```

### Получить вложения задачи
```
GET /tasks/{id}/attachments
```

### Загрузить вложение для комментария
```
POST /comments/{id}/attachments
```

### Удалить вложение
```
DELETE /attachments/{id}
```

**Допустимые типы:** `jpg, png, pdf, doc, docx, xls, xlsx, zip`
**Макс. размер:** 10MB

---

## 8. Habits

### Список привычек
```
GET /habits
```

### Создать привычку
```
POST /habits
```

**Body:**
```json
{
  "name": "Утренняя зарядка",
  "color": "#8B5CF6",
  "icon": "💪",
  "frequency": "daily",
  "target_days": [1, 2, 3, 4, 5]
}
```

**Frequency enum:** `daily`, `weekly`, `custom`
**target_days:** [0=Sun, 1=Mon, 2=Tue, 3=Wed, 4=Thu, 5=Fri, 6=Sat]

### Обновить привычку
```
PUT /habits/{id}
```

### Удалить привычку
```
DELETE /habits/{id}
```

### Отметить выполнение
```
POST /habits/{id}/log
```

**Body:**
```json
{
  "date": "2026-04-04"
}
```
> `date` опционален — по умолчанию сегодня

### Статистика привычки
```
GET /habits/{id}/stats?days=30
```

**Response:**
```json
{
  "success": true,
  "data": {
    "current_streak": 7,
    "best_streak": 14,
    "completion_rate": 85.5,
    "completions_last_30_days": ["2026-04-01", "2026-04-02", ...],
    "total_completions": 45
  }
}
```

---

## 9. Notifications

### Список уведомлений
```
GET /notifications
```

### Отметить как прочитанное
```
PUT /notifications/{id}/read
```

### Отметить все как прочитанные
```
PUT /notifications/read-all
```

### Счётчик непрочитанных
```
GET /notifications/unread-count
```

**Response:**
```json
{
  "success": true,
  "data": { "count": 5 }
}
```

---

## 10. AI Endpoints

### Сгенерировать подзадачи
```
POST /ai/generate-subtasks
```

**Body:**
```json
{
  "description": "Создать REST API для Task Manager",
  "count": 5
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "subtasks": [
      {
        "title": "Подзадача 1",
        "description": "Описание подзадачи 1 для: Создать REST API...",
        "priority": "medium"
      }
    ]
  }
}
```

### Предложить план
```
POST /ai/suggest-plan
```

**Body:**
```json
{
  "goal": "Запустить мобильное приложение"
}
```

> **Rate limit:** 20 запросов/час на пользователя

---

## 11. Export/Import

### Экспорт проекта
```
GET /projects/{id}/export?format=json
```

**Response (JSON):**
```json
{
  "success": true,
  "data": {
    "project": { "id": 1, "name": "My Project" },
    "tasks": [
      { "id": 1, "title": "Task 1", "status": "todo", "tags": ["urgent"] }
    ]
  },
  "meta": {
    "exported_at": "2026-04-04T10:00:00Z",
    "total_tasks": 15
  }
}
```

### Экспорт в CSV
```
GET /projects/{id}/export?format=csv
```
> Возвращает файл `tasks.csv` для скачивания

### Импорт задач
```
POST /import
```

**Body:**
```json
{
  "data": [
    {
      "title": "Imported Task",
      "status": "todo",
      "priority": "medium",
      "due_at": "2026-05-01T00:00:00Z"
    }
  ],
  "project_id": 15
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "imported": 2,
    "errors": []
  }
}
```

---

## 12. Health Check

```
GET /health
```

**Response:**
```json
{
  "success": true,
  "data": {
    "status": "ok",
    "services": {
      "database": "up",
      "redis": "up"
    },
    "timestamp": "2026-04-04T10:00:00Z"
  }
}
```

**Status values:** `ok`, `degraded`, `down`

---

## Ошибки и коды

| Код | HTTP | Описание |
|-----|------|----------|
| `VALIDATION_ERROR` | 422 | Ошибка валидации |
| `AUTH_INVALID_CREDENTIALS` | 401 | Неверный логин/пароль |
| `AUTH_EMAIL_NOT_VERIFIED` | 401 | Email не подтверждён |
| `AUTH_TOKEN_EXPIRED` | 401 | Токен истёк |
| `FORBIDDEN_PROJECT_ACCESS` | 403 | Нет доступа к проекту |
| `FORBIDDEN_TASK_ACCESS` | 403 | Нет доступа к задаче |
| `NOT_PROJECT_OWNER` | 403 | Не владелец проекта |
| `NOT_PROJECT_ADMIN` | 403 | Не админ проекта |
| `TASK_NOT_FOUND` | 404 | Задача не найдена |
| `PROJECT_NOT_FOUND` | 404 | Проект не найден |
| `USER_NOT_FOUND` | 404 | Пользователь не найден |
| `RATE_LIMIT_EXCEEDED` | 429 | Превышен лимит |
| `TASK_ALREADY_COMPLETED` | 422 | Задача уже завершена |
| `INVALID_RECURRING_RULE` | 422 | Неверное правило повторения |

---

## Rate Limiting

| Endpoint | Лимит | Окно |
|----------|-------|------|
| API (общий) | 100 req | 1 мин |
| Загрузка файлов | 10 uploads | 1 мин |
| AI endpoints | 20 req | 1 час |
| Auth попытки | 5 attempts | 1 мин |
| Поиск | 30 req | 1 мин |

**Заголовки в ответе:**
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1680516000
Retry-After: 60  (если лимит исчерпан)
```

---

## ACL (Матрица прав)

| Действие | Owner | Admin | Member | Viewer |
|----------|:-----:|:-----:|:------:|:------:|
| Удалить/Архив проект | ✅ | ❌ | ❌ | ❌ |
| Пригласить/Удалить | ✅ | ✅ | ❌ | ❌ |
| Создать задачу | ✅ | ✅ | ✅ | ❌ |
| Редактировать свою задачу | ✅ | ✅ | ✅ | ❌ |
| Редактировать чужую задачу | ✅ | ✅ | ❌ | ❌ |
| Создать комментарий | ✅ | ✅ | ✅ | ❌ |
| Удалить свой комментарий | ✅ | ✅ | ✅ | ❌ |
| Удалить чужой комментарий | ✅ | ✅ | ❌ | ❌ |
| Управление тегами | ✅ | ✅ | ❌ | ❌ |
| Загрузить вложение | ✅ | ✅ | ✅ | ❌ |
| Просмотр | ✅ | ✅ | ✅ | ✅ |

> **Owner** — `projects.owner_id === user.id`
> **Author** — `task.created_by === user.id`

---

## WebSocket Events (Reverb)

### Каналы
- `private-user.{userId}` — личные уведомления
- `private-project.{projectId}` — обновления проекта

### События

| Событие | Канал | Payload |
|---------|-------|---------|
| `task.created` | `private-project.{id}` | `{ task, project_id }` |
| `task.updated` | `private-project.{id}` | `{ task, changes, project_id }` |
| `task.deleted` | `private-project.{id}` | `{ task_id, project_id }` |
| `task.assigned` | `private-project.{id}` | `{ task, assignee_id, assigned_by }` |
| `comment.created` | `private-project.{id}` | `{ comment, task_id, project_id }` |
| `project.member.added` | `private-user.{id}` | `{ member, project_id }` |
| `notification.new` | `private-user.{id}` | `{ notification }` |

---

## Быстрый старт (Local Dev)

```bash
# 1. Клонировать репозиторий
git clone https://github.com/ewgenm/tst.git
cd tst

# 2. Установить зависимости
composer install

# 3. Настроить .env
cp .env.example .env
# Изменить DB_*, APP_URL и другие настройки

# 4. Создать базу данных и запустить миграции
php artisan key:generate
php artisan migrate --seed

# 5. Запустить сервер
php artisan serve --port=8000
```

### Тестовые креды
```
Email: admin@tasksync.test
Password: password
```

---

## Контакты

- **GitHub:** https://github.com/ewgenm/tst
- **Документация:** `user_docs/` в репозитории

# Project Structure

A map of the main files and folders in the current Laravel + Inertia React application.

---

## Root Directory

```text
php_project/
|-- app/
|-- bootstrap/
|-- config/
|-- database/
|-- public/
|-- resources/
|-- routes/
|-- storage/
|-- tests/
|-- vendor/
|-- node_modules/
|-- artisan
|-- composer.json
|-- composer.lock
|-- package.json
|-- package-lock.json
|-- phpunit.xml
|-- vite.config.js
```

### Root-Level Files

| File | Purpose |
|---|---|
| `.env` | Local environment configuration. Gitignored. |
| `.env.example` | Environment template. |
| `artisan` | Laravel CLI entry point. |
| `composer.json` | PHP dependencies and Composer scripts. |
| `composer.lock` | Locked PHP dependency versions. |
| `package.json` | npm dependencies and scripts. |
| `package-lock.json` | Locked Node dependency versions. |
| `phpunit.xml` | Pest/PHPUnit test configuration. |
| `vite.config.js` | Vite config with Laravel, React, Tailwind, and Bunny font plugins. |

---

## `app/` - Application Logic

```text
app/
|-- Http/
|   |-- Controllers/
|   |   |-- AuthController.php
|   |   |-- ChecklistItemController.php
|   |   |-- Controller.php
|   |   |-- IdeaTaskController.php
|   |   `-- IdeaController.php
|   `-- Middleware/
|       `-- HandleInertiaRequests.php
|-- Models/
|   |-- Idea.php
|   `-- User.php
|-- Services/
|   |-- ActionTasks/
|   |-- Ai/
|   |-- Checklists/
|   |-- CoreFeatures/
|   |-- DesiredOutcomes/
|   |-- MvpScopes/
|   |-- ProblemStatements/
|   `-- TargetUsers/
`-- Providers/
```

### Controllers

| File | Purpose |
|---|---|
| `AuthController.php` | Shows Inertia login/register pages and handles register, login, and logout actions. |
| `IdeaController.php` | Handles idea index, create, detail, update, and delete actions. Returns Inertia pages for the idea UI. |
| `IdeaTaskController.php` | Handles Action Plan overview, phase pages, and task status updates. |
| `ChecklistItemController.php` | Handles checklist item add, edit/toggle, and delete actions. |
| `Controller.php` | Base controller class. |

### Middleware

| File | Purpose |
|---|---|
| `HandleInertiaRequests.php` | Registers the Inertia root view and shares auth user, flash messages, and validation errors with React pages. |

### Models

| File | Table | Purpose |
|---|---|---|
| `Idea.php` | `ideas` | User-owned idea record with JSON roadmap/checklist/action task casting and a `user()` relationship. |
| `User.php` | `users` | Authenticated user model with a `ideas()` relationship and hashed password casting. |

### Services

| File/Folder | Purpose |
|---|---|
| `ActionTasks/ActionTasks.php` | Local fallback task set and normalization for task categories, phases, phase slugs, priorities, and statuses. |
| `Ai/RoadmapGenerator.php` | Calls the Node/LangChain roadmap generator and falls back to local roadmap data. |
| `Checklists/ChecklistItems.php` | Checklist fallback, AI response parsing, legacy normalization, and stored item normalization. |
| `CoreFeatures/`, `DesiredOutcomes/`, `MvpScopes/`, `ProblemStatements/`, `TargetUsers/` | Normalization and fallback services for generated roadmap sections. |

---

## `bootstrap/`

| File | Purpose |
|---|---|
| `app.php` | Laravel application bootstrap. Registers `HandleInertiaRequests` in the web middleware group. |

---

## `database/`

```text
database/
|-- database.sqlite
|-- factories/
|-- migrations/
`-- seeders/
```

### Important Migrations

| File | Purpose |
|---|---|
| `0001_01_01_000000_create_users_table.php` | Creates users table. |
| `0001_01_01_000001_create_cache_table.php` | Creates database cache tables. |
| `0001_01_01_000002_create_jobs_table.php` | Creates queue/job tables. |
| `2026_05_20_085146_create_ideas_table.php` | Creates initial ideas table. |
| `2026_05_22_142900_add_user_id_to_ideas_table.php` | Adds user ownership to ideas. |
| `2026_05_24_000001_add_name_and_checklist_to_ideas_table.php` | Adds idea names and checklist JSON storage. |
| `2026_05_24_000002_convert_checklists_to_editable_items.php` | Converts checklist entries into editable item objects. |
| `2026_05_25_000001_add_target_user_to_ideas_table.php` | Adds generated target user JSON storage. |
| `2026_05_25_000002_add_problem_statement_to_ideas_table.php` | Adds generated problem statement storage. |
| `2026_05_25_000003_add_desired_outcome_to_ideas_table.php` | Adds generated desired outcome storage. |
| `2026_05_25_000004_add_core_features_and_mvp_scope_to_ideas_table.php` | Adds generated core features and MVP scope JSON storage. |
| `2026_05_26_000001_add_action_tasks_to_ideas_table.php` | Adds Action Plan task JSON storage. |

---

## `resources/` - Frontend Source

```text
resources/
|-- css/
|   `-- app.css
|-- js/
|   |-- app.js
|   |-- Components/
|   `-- Pages/
`-- views/
    `-- app.blade.php
```

### CSS

| File | Purpose |
|---|---|
| `resources/css/app.css` | Tailwind CSS v4 entry point and `Instrument Sans` theme setup. Scans Blade, JS, and JSX files. |

### JavaScript

| File/Folder | Purpose |
|---|---|
| `resources/js/app.js` | Inertia React bootstrap. Resolves React pages from `resources/js/Pages`. |
| `resources/js/Components/` | Shared React UI components: layout, buttons, inputs, auth card, idea card, inline editor, checklist item, action task card, and action task modal. |
| `resources/js/Pages/` | React page components for Ideas, task phases, Auth, About, and Contact. |

### Views

| File | Purpose |
|---|---|
| `resources/views/app.blade.php` | The only active Blade UI file. Provides the HTML shell for Inertia and loads Vite assets. |

---

## `routes/`

```text
routes/
|-- web.php
`-- console.php
```

### `routes/web.php`

| Method | URI | Action |
|---|---|---|
| `GET` | `/register` | `AuthController@showRegisterForm` |
| `POST` | `/register` | `AuthController@register` |
| `GET` | `/login` | `AuthController@showLoginForm` |
| `POST` | `/login` | `AuthController@login` |
| `POST` | `/logout` | `AuthController@logout` |
| `GET` | `/` | `IdeaController@index` |
| `POST` | `/ideas` | `IdeaController@store` |
| `GET` | `/ideas/{idea}` | `IdeaController@show` |
| `PATCH` | `/ideas/{idea}` | `IdeaController@update` |
| `DELETE` | `/ideas/{idea}` | `IdeaController@destroy` |
| `GET` | `/ideas/{idea}/tasks` | `IdeaTaskController@index` |
| `GET` | `/ideas/{idea}/tasks/{category}/{phaseSlug}` | `IdeaTaskController@phase` |
| `PATCH` | `/ideas/{idea}/tasks/{taskId}` | `IdeaTaskController@update` |
| `POST` | `/ideas/{idea}/checklist-items` | `ChecklistItemController@store` |
| `PATCH` | `/ideas/{idea}/checklist-items/{itemId}` | `ChecklistItemController@update` |
| `DELETE` | `/ideas/{idea}/checklist-items/{itemId}` | `ChecklistItemController@destroy` |
| `GET` | `/about` | Inertia `About` page |
| `GET` | `/contact` | Inertia `Contact` page |

---

## `public/`

The web root. Laravel serves requests through `public/index.php`. Vite production builds are written to `public/build/`.

---

## `tests/`

```text
tests/
|-- Feature/
|   |-- AuthenticationTest.php
|   |-- IdeaAuthorizationTest.php
|   `-- ExampleTest.php
|-- Unit/
`-- Pest.php
```

Feature tests cover:

- Authentication pages and flows.
- Inertia page responses for auth and idea pages.
- Idea ownership and authorization.
- Idea creation, editing, viewing, and deletion.
- Action Plan fallback task normalization, categories, phase slugs, task phase pages, status updates, and authorization.
- Checklist item creation, editing, toggling, deletion, and migration behavior.

---

## Dependency Folders

| Folder | Purpose |
|---|---|
| `vendor/` | Composer-installed PHP dependencies. |
| `node_modules/` | npm-installed JavaScript dependencies. |

Both folders are generated dependency folders and should not be edited manually.

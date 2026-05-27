# Progress Log

A running technical record of every feature built, what files were changed, and exactly how it was implemented.

---

## Feature 7 - Async Roadmap Generation UX
**Date:** 2026-05-27
**Status:** Complete

---

### Overview

Moved AI roadmap generation out of the browser request. Creating an idea now saves a placeholder record immediately, redirects back to the ideas page, and processes AI generation in a queued job. The ideas page shows a generating skeleton card with rotating progress text until polling sees that the idea is complete.

The current generation flow is:

```text
POST /ideas
  -> create idea with state = generating
  -> dispatch GenerateIdeaRoadmap
  -> redirect to /

queue worker
  -> run RoadmapGenerator
  -> save generated roadmap fields
  -> mark idea done

ideas page
  -> show generating card
  -> poll while any idea is generating
```

---

### What Was Built

#### 1. Queued roadmap job

Added `app/Jobs/GenerateIdeaRoadmap.php`.

The job:

- loads the idea by id
- calls `RoadmapGenerator`
- saves target user, problem statement, desired outcome, core features, MVP scope, action phases, fallback action tasks, and checklist
- marks the idea `done` on success
- marks the idea `failed` if generation throws or the queued job fails

#### 2. Fast idea creation

Updated `IdeaController@store` so it no longer waits for OpenAI before redirecting. It now creates the idea with:

- `state = generating`
- fallback action phases
- fallback action tasks
- empty checklist

Then it dispatches `GenerateIdeaRoadmap`.

#### 3. Generating state UI

Added:

- `resources/js/Components/GeneratingIdeaCard.jsx`
- `resources/js/Components/GenerationProgressText.jsx`
- `resources/js/hooks/useGeneratingIdeasPoll.js`

The idea board now shows a dedicated skeleton loading card for generating ideas. The progress copy rotates through estimated messages such as understanding the idea, defining the target user, shaping MVP scope, planning phases, and preparing the roadmap.

#### 4. Polling

The ideas index reloads only the `ideas` prop every few seconds while at least one idea is in the `generating` state. Polling stops automatically when no generating ideas remain.

#### 5. Status badges

Updated `StatusBadge` to support:

- `generating`
- `done`
- `failed`
- `pending`

#### 6. Tests

Updated `tests/Feature/IdeaAuthorizationTest.php` so creation and generation are tested separately:

- posting `/ideas` creates a `generating` idea and queues the job
- the queued job fills generated sections and marks the idea `done`
- the queued job marks the idea `failed` when generation throws
- the ideas index serializes generating ideas

---

### Runtime Note

Async generation requires a queue worker:

```text
php artisan queue:work
```

The existing `composer dev` script already starts `php artisan queue:listen --tries=1` alongside Laravel and Vite.

---

### Verification

```text
npm run build
  -> Passed
```

PHP tests could not be run from the current shell because `php` was not available on PATH.

---

## Feature 6 - AI Roadmap Phases and Phase-First Task UI
**Date:** 2026-05-27
**Status:** Complete

---

### Overview

Added AI-generated roadmap phases and changed the Action Plan page so phases are the first/default tab. A phase represents a major execution stage and can contain product, marketing, and validation work together. Tasks remain separate and categorized, ready for later AI task generation per phase.

---

### What Was Built

#### 1. Action phase storage and normalization

Added `action_phases` JSON storage with `database/migrations/2026_05_27_000001_add_action_phases_to_ideas_table.php`.

Added `app/Services/ActionPhases/ActionPhases.php` to normalize:

- phase title/name
- slug
- description
- primary category
- included categories
- goal
- success criteria
- order

Older ideas fall back to local roadmap phases.

#### 2. AI phase prompt and bridge support

Added `resources/prompts/roadmap/phases.md`.

Updated `resources/js/ai/generate-roadmap.mjs` so the roadmap payload includes `phases` in addition to target user, problem statement, desired outcome, core features, MVP scope, and checklist.

Updated `RoadmapGenerator` so AI phases are normalized and returned as `action_phases`.

#### 3. Phase-first Action Plan

Updated `resources/js/Pages/Ideas/Tasks.jsx`.

The Action Plan tabs are now:

- Phases
- Product tasks
- Marketing tasks
- Market validation

The Phases tab displays AI phase cards. Category tabs display tasks directly in pending/completed columns.

#### 4. Global phase page

Added:

```text
GET /ideas/{idea}/tasks/phases/{phaseSlug}
```

The global phase page shows phase description, goal, success criteria, included categories, and all matching tasks across categories. Valid generated phases with no matching tasks render an empty task state instead of returning 404.

#### 5. Tests

Added `tests/Unit/ActionPhasesTest.php` and extended feature/unit tests for:

- AI phase normalization
- category validation
- fallback phases
- phase metadata serialization
- global phase pages with mixed-category tasks
- generated phases with no matching tasks
- missing phase 404s

---

### Verification

```text
npm run build
  -> Passed

node --check resources/js/ai/generate-roadmap.mjs
  -> Passed
```

PHP tests could not be run from the current shell because `php` was not available on PATH.

---

## Feature 5 - Action Plan Tasks, Categories, Phases, and Task Modals
**Date:** 2026-05-26
**Status:** Complete

---

### Overview

Added an Action Plan workflow after the generated MVP scope on the idea detail page. The first implementation uses local fallback task data rather than AI generation, but it stores the tasks on each idea as JSON so the later AI integration can replace the local task source without redesigning the UI.

The current task flow is:

```text
Idea detail
  -> Action Plan preview
  -> Action Plan page
  -> Category tabs
  -> Phase cards
  -> Dedicated phase page
  -> Task cards
  -> Task detail modal
```

---

### What Was Built

#### 1. Action task storage and normalization

Added `action_tasks` JSON storage to ideas with `database/migrations/2026_05_26_000001_add_action_tasks_to_ideas_table.php`.

Added `app/Services/ActionTasks/ActionTasks.php` to provide:

- local fallback task content
- task status normalization: `pending`, `completed`
- task categories: `product`, `marketing`, `validation`
- human phase names and URL-safe `phaseSlug` values
- priority normalization: `High`, `Medium`, `Low`
- backward-compatible defaults for older stored task shapes

New ideas are seeded with fallback action tasks from `IdeaController`.

---

#### 2. Action Plan routes and controller

Added `app/Http/Controllers/IdeaTaskController.php`.

Routes added:

```text
GET   /ideas/{idea}/tasks
GET   /ideas/{idea}/tasks/{category}/{phaseSlug}
PATCH /ideas/{idea}/tasks/{taskId}
```

The controller authorizes idea ownership, returns Inertia pages for the task overview and phase pages, and persists task status changes back into the idea's `action_tasks` JSON.

---

#### 3. Idea detail Action Plan preview

Updated `resources/js/Pages/Ideas/Show.jsx` to place an Action Plan preview after MVP Scope.

The preview shows the first few task cards, completion progress, and a link to the full Action Plan page.

---

#### 4. Category and phase task overview

Added/updated `resources/js/Pages/Ideas/Tasks.jsx`.

The page now has three category tabs:

- Product tasks
- Marketing tasks
- Market validation

Within the selected category, tasks are grouped into phase cards. Each phase card shows total, completed, and open counts. Opening a phase card navigates to the dedicated phase page.

---

#### 5. Dedicated phase page and task modal

Added `resources/js/Pages/Ideas/TaskPhase.jsx`.

The phase page shows the tasks for one category/phase in Pending and Completed columns. Clicking a task opens a detail modal.

Added shared task UI:

- `resources/js/Components/ActionTaskRow.jsx`
- `resources/js/Components/ActionTaskModal.jsx`

The modal shows task category, phase, priority, status, detail copy, next action guidance, and a status update button.

---

#### 6. Tests

Added `tests/Unit/ActionTasksTest.php` and extended `tests/Feature/IdeaAuthorizationTest.php` for:

- fallback task shape
- categories and phase slugs
- stored task normalization
- task overview access
- phase page access
- task status updates
- invalid status validation
- missing task IDs
- authorization failures

---

### Verification

```text
npm run build
  -> Passed
```

PHP tests could not be run from the current shell because `php` and `composer` were not available on PATH.

---

## Feature 4 - Laravel + Inertia React Frontend Migration
**Date:** 2026-05-24
**Status:** Complete

---

### Overview

Migrated the project from a Blade-page frontend to a Laravel + Inertia + React architecture. Laravel still owns routing, session authentication, validation, authorization, redirects, and database persistence. React now owns the interactive browser UI through Inertia pages and shared components.

This keeps the project as one Laravel application while giving the frontend more room for advanced interactions.

---

### What Was Built

#### 1. Inertia and React dependencies

Added the Inertia/React frontend stack:

- `inertiajs/inertia-laravel`
- `@inertiajs/react`
- `react`
- `react-dom`
- `@vitejs/plugin-react`
- `lucide-react`
- `axios`

Updated `vite.config.js` to use the React plugin alongside Laravel Vite and Tailwind CSS.

---

#### 2. Inertia root and middleware

Added `resources/views/app.blade.php` as the single Inertia root Blade view. It provides the HTML shell, CSRF token, Vite assets, `@inertiaHead`, and `@inertia`.

Added `app/Http/Middleware/HandleInertiaRequests.php` and registered it in `bootstrap/app.php`. Shared props now include:

- authenticated user data
- validation errors
- success flash messages

---

#### 3. React application bootstrap

Replaced the empty `resources/js/app.js` with the Inertia React bootstrap:

- sets page titles
- resolves pages from `resources/js/Pages/**/*.jsx`
- mounts React with `createRoot`
- enables Inertia progress color

---

#### 4. React pages and components

Added React pages:

- `resources/js/Pages/Ideas/Index.jsx`
- `resources/js/Pages/Ideas/Show.jsx`
- `resources/js/Pages/Auth/Login.jsx`
- `resources/js/Pages/Auth/Register.jsx`
- `resources/js/Pages/About.jsx`
- `resources/js/Pages/Contact.jsx`

Added shared components:

- `AppLayout`
- `AuthCard`
- `Button`
- `TextInput`
- `FormField`
- `ErrorSummary`
- `IdeaCard`
- `InlineEditor`
- `ChecklistItem`

The UI keeps the existing dark, focused visual direction while adding clearer component structure, loading states, inline editing, and Inertia-powered interactions.

---

#### 5. Controller refactor

Moved idea and checklist logic out of `routes/web.php`:

- `IdeaController` now handles listing, creating, showing, updating, and deleting ideas.
- `ChecklistItemController` now handles adding, editing/toggling, and deleting checklist items.
- `AuthController` now returns Inertia pages for login and registration.

Routes and route names were preserved.

---

#### 6. Removed old Blade UI files

Deleted the old Blade page/component UI files:

- `resources/views/ideas.blade.php`
- `resources/views/ideas/show.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/auth/register.blade.php`
- `resources/views/about.blade.php`
- `resources/views/contact.blade.php`
- `resources/views/components/layout.blade.php`
- `resources/views/components/card.blade.php`

Blade remains in use only as the Inertia root view.

---

### Verification

```text
npm run build
  -> Passed

composer test
  -> Passed: 19 tests, 118 assertions

composer validate --no-check-publish
  -> Passed
```

HTTP smoke check:

```text
GET http://php_project.test/login
  -> 200
  -> Serves the Inertia root view
```

Browser automation could not complete because the in-app browser connector failed during setup, but the Laravel response, Vite build, and test suite all passed.

---

## Feature 2 - Idea Detail Checklist Prototype
**Date:** 2026-05-24
**Status:** Complete

---

### Overview

Added a first version of the idea planning workflow. Users now create ideas with a short idea name and a longer description. Each idea is listed as a clickable item on the board, and opening it shows a dedicated detail page with a stored, read-only generic checklist.

This is intentionally a non-AI implementation for now: every new idea receives the same structured checklist. Because the checklist is stored per idea as JSON, the later AI integration can replace the generic checklist generation without redesigning the database or UI flow.

---

### What Was Built

#### 1. Idea schema update - `database/migrations/2026_05_24_000001_add_name_and_checklist_to_ideas_table.php` *(new)*

Added two columns to the `ideas` table:

```php
$table->string('name')->nullable()->after('user_id');
$table->json('checklist')->nullable()->after('description');
```

The migration backfills existing ideas:
- `name` is generated from the first 80 characters of the existing description.
- `checklist` is populated with the default generic checklist.

The columns are left nullable at the database level to avoid database-driver-specific column alteration issues, while application validation ensures new ideas always include both values.

---

#### 2. `Idea.php` - `app/Models/Idea.php` *(updated)*

Added an Eloquent cast so `$idea->checklist` is returned as a PHP array:

```php
protected function casts(): array
{
    return [
        'checklist' => 'array',
    ];
}
```

---

#### 3. `routes/web.php` *(updated)*

Added a `genericIdeaChecklist()` helper that returns the current placeholder checklist:

```php
[
    'Clarify the problem this idea solves.',
    'Identify who the idea is for.',
    'List the smallest useful version.',
    'Decide the first three actions to build or validate it.',
    'Define what success looks like.',
]
```

Updated `POST /ideas` validation:
- `name` is required, string, max 120 characters.
- `description` is required, string, max 2000 characters.

New ideas are created with the submitted name and description, the generic checklist, and an initial `pending` state.

Added `GET /ideas/{idea}` as `ideas.show`. The route uses route model binding and checks ownership before rendering the detail page:

```php
abort_unless($idea->user_id === auth()->id(), 403);
```

---

#### 4. `ideas.blade.php` - `resources/views/ideas.blade.php` *(rewritten)*

Updated the home page form:
- Replaced the single `idea` textarea with an `Idea name` input and `Description` textarea.
- Preserves validation errors and `old()` input values.

Updated the idea list:
- Shows the idea name as the primary text.
- Shows a shortened description preview.
- Keeps relative created time and status badge.
- Adds a visible arrow affordance and hover/focus styles so users can tell each idea is clickable.
- Keeps delete as a separate form/button, avoiding invalid nested interactive elements.

---

#### 5. Detail page - `resources/views/ideas/show.blade.php` *(new)*

Added a new read-only detail page for one idea. It shows:
- Back link to the idea board.
- Status badge and created date.
- Idea name.
- Full idea description.
- Checklist item count.
- Stored checklist items as read-only checklist rows with check icons.

The page uses the existing dark Tailwind design system and the shared `<x-layout>` wrapper.

---

#### 6. Feature tests - `tests/Feature/IdeaAuthorizationTest.php` *(updated)*

Updated existing tests for the new create payload and added coverage for the new detail flow:
- Guests are redirected away from the idea board, create route, and idea detail route.
- Authenticated users only see their own ideas.
- Authenticated users can create ideas with `name`, `description`, and stored generic checklist.
- Authenticated users can view their own idea checklist page.
- Users cannot view another user's idea detail page.
- Users still cannot delete another user's idea.

---

### Technical Flow

```text
Authenticated user submits idea form
  -> POST /ideas
  -> validate name + description
  -> create Idea with pending state and generic checklist
  -> redirect back to /

Authenticated user views idea board
  -> GET /
  -> load only current user's ideas
  -> render each idea as a clickable list item

Authenticated user opens an idea
  -> GET /ideas/{idea}
  -> route model binding loads the idea
  -> ownership check returns 403 if it belongs to another user
  -> render idea detail page with stored checklist
```

---

### Verification

```text
npm run build
  -> Passed

C:\Users\ISAAC ONUEGBU\.config\herd\bin\php84\php.exe artisan test
  -> Passed: 13 tests, 46 assertions
```

---

## Feature 1 — Full Authentication System
**Date:** 2026-05-22
**Status:** ✅ Complete

---

### Overview

Implemented a complete, from-scratch authentication system covering user registration, login (with remember-me), logout, session management, and route protection. No starter kit (Breeze/Jetstream) was used — everything was built manually to keep full control and visibility over the implementation.

---

### What Was Built

#### 1. `AuthController` — `app/Http/Controllers/AuthController.php` *(new)*

A dedicated controller with three logical groups:

**Register:**
- `showRegisterForm()` — returns the `auth.register` view.
- `register(Request $request)` — validates name, email (unique), and password (with confirmation and Laravel's `Password::min(8)` rule). On success, calls `User::create()` (password is auto-hashed by the `hashed` cast on the User model), immediately logs the new user in via `Auth::login($user)`, and redirects to `/` with a flash success message.

**Login:**
- `showLoginForm()` — returns the `auth.login` view.
- `login(Request $request)` — validates credentials, calls `Auth::attempt($credentials, $remember)` where `$remember` is derived from a boolean checkbox. On success, calls `$request->session()->regenerate()` to prevent session fixation attacks, then redirects to the intended page (or `/`). On failure, returns back with the email pre-filled and a field-specific error on `email`.

**Logout:**
- `logout(Request $request)` — calls `Auth::logout()`, then `session()->invalidate()` and `session()->regenerateToken()` to fully destroy the session and rotate the CSRF token. Redirects to `/login` with a flash message.

---

#### 2. `register.blade.php` — `resources/views/auth/register.blade.php` *(new)*

- Full registration form: Name, Email, Password, Confirm Password fields.
- Uses `old()` to repopulate inputs after validation failure (except passwords).
- Displays all validation errors in a styled red alert block.
- Tailwind-styled dark card design with `bg-white/5`, `border border-white/10`, `backdrop-blur`, rounded inputs with focus ring.
- Uses `<x-layout>` wrapper so it inherits the shared nav and flash message.
- Link to `/login` for users who already have an account.

---

#### 3. `login.blade.php` — `resources/views/auth/login.blade.php` *(new)*

- Login form: Email, Password, Remember Me checkbox.
- Displays validation errors and session flash messages.
- `old('email')` repopulates the email on failed attempt.
- Remember-me checkbox passes `remember` boolean to `Auth::attempt()`.
- Same Tailwind dark card design as registration for visual consistency.
- Link to `/register` for new users.

---

#### 4. `layout.blade.php` — `resources/views/components/layout.blade.php` *(rewritten)*

The shared layout component was fully rebuilt:

- **Fixed HTML bug:** Removed the duplicate `<head>` tag that existed in the original.
- **Navigation bar added:** Sticky top nav (`sticky top-0 z-50`) with glassmorphism backdrop (`backdrop-blur-md`).
- **Auth-aware nav links using `@auth` / `@else` directives:**
  - **Logged in:** Shows the authenticated user's name, About, Contact links, and a POST logout button (inside a `<form>` with `@csrf` — never a GET link, to protect against CSRF logout attacks).
  - **Guest:** Shows Sign In and Register links.
- **Flash message zone:** After the nav, renders a styled green success banner when `session('success')` is set.
- **Background changed** from `bg-gray-700` to `bg-gray-900` for a deeper, more polished dark theme.
- **Max-width container** applied consistently to nav and main content (`max-w-3xl mx-auto`).
- **Default title prop** changed from `'laracasts'` to `'Idea Board'`.

---

#### 5. `routes/web.php` *(rewritten)*

The routes file was completely restructured into three logical groups:

**Guest-only group** (`middleware('guest')`) — redirects to `/` if already logged in:
```
GET  /register  → AuthController@showRegisterForm
POST /register  → AuthController@register
GET  /login     → AuthController@showLoginForm
POST /login     → AuthController@login
```

**Auth-protected group** (`middleware('auth')`) — redirects to `/login` if not authenticated:
```
GET    /          → Fetches current user's ideas (all statuses), renders ideas view
POST   /ideas     → Validates input, creates Idea with user_id = auth()->id(), redirects with flash
DELETE /ideas/{idea} → Checks ownership (403 if not owner), deletes idea, redirects with flash
```

**Public routes** (no middleware):
```
GET /about    → about view
GET /contact  → contact view
```

Key improvements over original:
- Ideas are now scoped to `auth()->id()` — users only ever see their own ideas.
- Input is validated (`required`, `string`, `max:1000`) before saving — empty submissions are rejected.
- The delete route uses Laravel's route model binding (`Idea $idea`) for automatic 404 if not found, plus an explicit 403 ownership check.
- The old session-based delete (`session()->forget('ideas')`) was removed.
- The duplicate `/contact` route registration was removed.
- All routes are named (`register`, `login`, `logout`, `home`, `about`, `contact`).

---

#### 6. Migration — `database/migrations/2026_05_22_142900_add_user_id_to_ideas_table.php` *(new)*

```php
$table->foreignId('user_id')
      ->nullable()
      ->after('id')
      ->constrained()
      ->cascadeOnDelete();
```

- Added a `user_id` foreign key column to the `ideas` table.
- `nullable()` — allows existing idea rows (created before auth existed) to remain without breaking.
- `constrained()` — enforces referential integrity with the `users` table.
- `cascadeOnDelete()` — when a user is deleted, all their ideas are automatically deleted too.
- The `down()` method calls `dropForeignIdFor(User::class)` then `dropColumn('user_id')` for clean rollbacks.

---

#### 7. `Idea.php` — `app/Models/Idea.php` *(updated)*

Added the inverse Eloquent relationship:

```php
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

This allows `$idea->user` to retrieve the owning `User` model instance anywhere in the application.

---

#### 8. `ideas.blade.php` — `resources/views/ideas.blade.php` *(rewritten)*

Fully rebuilt the home/ideas page:

- **Form section:** Styled card with a `<textarea>` using `old('idea')` for repopulation, validation error display, and a "Save Idea" submit button.
- **Ideas list:** Iterates all of the user's ideas (not just `done` ones — fixing the core bug from the original). Each row shows:
  - The idea description
  - A `diffForHumans()` relative timestamp (e.g. "2 minutes ago")
  - A colour-coded status badge: yellow for `pending`, green for `done`
  - A hover-reveal delete button (trash icon) that submits a `DELETE` form with a JavaScript `confirm()` guard
- **Empty state:** A centred placeholder with an icon shown when the user has no ideas yet.

---

### Bugs Fixed in This Feature

| Bug | Fix |
|---|---|
| Newly submitted ideas never appeared (list filtered `state = 'done'` but new ideas saved as `pending`) | Home page now fetches **all** of the user's ideas regardless of state |
| Duplicate `<head>` tag in layout | Removed the second empty `<head>` tag |
| Duplicate `/contact` route registration | Removed the redundant `Route::view('/contact', 'contact')` |
| No input validation on idea submission | Added `required`, `string`, `max:1000` validation rules |
| Delete route cleared session instead of deleting a record | Replaced with proper route model binding + DB delete |
| Ideas were global (any visitor saw all ideas) | Ideas are now scoped to `auth()->id()` |

---

### How Auth Flow Works End-to-End

```
Guest visits /
  → Redirected to /login  (auth middleware)

Guest visits /register
  → Sees registration form
  → Submits: validates → creates User → Auth::login() → redirect /

Guest visits /login
  → Sees login form
  → Submits: Auth::attempt() → session regenerate → redirect /

Authenticated user visits /
  → Sees their own ideas
  → Can submit new ideas (POST /ideas)
  → Can delete their ideas (DELETE /ideas/{id})

Authenticated user clicks "Log out"
  → POST /logout → Auth::logout() → session invalidate → redirect /login
```

---

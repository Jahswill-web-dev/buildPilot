# Idea Board - Project Documentation

A Laravel idea-planning app with an Inertia React frontend.

---

## What Is This Project?

Idea Board is a small authenticated web application for capturing ideas, turning them into startup roadmaps, and tracking the next steps for each idea.

The backend is Laravel. The frontend is now React through Inertia, which means Laravel still owns routing, sessions, validation, authorization, and database work, while React owns the interactive browser UI.

---

## What It Does Right Now

- **Register, sign in, and log out** using Laravel session authentication.
- **Create ideas** with a name and description.
- **Generate startup roadmaps with AI** when new ideas are saved, including a target user profile, problem statement, desired outcome, core features, MVP scope, and editable checklist.
- **Review an Action Plan** generated with local placeholder tasks for now. Tasks are grouped by category, then phase, then task.
- **Browse task categories** for Product tasks, Marketing tasks, and Market validation.
- **Open phase pages** from each task category to focus on the tasks inside that phase.
- **Open task detail modals** from phase pages and toggle task status between pending and completed.
- **View your own ideas only** on the main board.
- **Open an idea detail page** with its full description and checklist.
- **Edit idea name and description** inline.
- **Add, edit, toggle, and delete checklist items**.
- **Delete ideas** you own.
- **See validation errors and success flash messages** through Inertia shared props.
- **Navigate between pages** with Inertia links for an app-like feel.

---

## Pages

| Page | What You'll Find There |
|---|---|
| **Home (`/`)** | Auth-protected idea creation form and your saved idea list |
| **Idea detail (`/ideas/{idea}`)** | Editable idea details, generated roadmap sections, Action Plan preview, and checklist workflow |
| **Action Plan (`/ideas/{idea}/tasks`)** | Category tabs and phase cards for product, marketing, and market validation tasks |
| **Task phase (`/ideas/{idea}/tasks/{category}/{phaseSlug}`)** | Pending/completed task board for one phase, with task detail modal |
| **Login (`/login`)** | React/Inertia sign-in form |
| **Register (`/register`)** | React/Inertia account creation form |
| **About (`/about`)** | Short product overview |
| **Contact (`/contact`)** | Basic project contact page |

---

## Current Architecture

```text
Laravel routes/controllers
  -> return Inertia pages + props
  -> React renders pages in the browser
  -> Inertia form helpers submit back to Laravel
  -> Laravel validates, authorizes, persists, redirects
```

Important frontend files:

- `resources/views/app.blade.php` - the single Inertia root Blade view.
- `resources/js/app.js` - Inertia React bootstrap.
- `resources/js/Pages/` - React page components.
- `resources/js/Components/` - shared UI components.
- `resources/css/app.css` - Tailwind CSS v4 entry point.

Important backend files:

- `routes/web.php` - named web routes.
- `app/Http/Controllers/AuthController.php` - registration, login, logout.
- `app/Http/Controllers/IdeaController.php` - idea list/create/show/update/delete.
- `app/Http/Controllers/IdeaTaskController.php` - Action Plan overview, phase pages, and task status updates.
- `app/Http/Controllers/ChecklistItemController.php` - checklist item create/update/delete.
- `app/Services/ActionTasks/ActionTasks.php` - Action Plan fallback tasks, categories, phases, and normalization logic.
- `app/Services/Ai/RoadmapGenerator.php` - Laravel service that calls the LangChain roadmap bridge.
- `app/Services/Checklists/ChecklistItems.php` - checklist fallback and normalization logic.
- `app/Services/CoreFeatures/CoreFeatures.php` - core feature fallback and normalization logic.
- `app/Services/MvpScopes/MvpScope.php` - MVP scope fallback and normalization logic.
- `app/Services/TargetUsers/TargetUserProfile.php` - target user fallback and normalization logic.
- `resources/js/ai/generate-roadmap.mjs` - staged LangChain/OpenAI bridge used during idea creation.
- `resources/prompts/roadmap/` - dedicated prompt files for each AI roadmap stage.
- `app/Http/Middleware/HandleInertiaRequests.php` - shared Inertia props.

---

## How to Run It Locally

> You need PHP 8.3+, Composer, and Node.js. If you use Laravel Herd, PHP and local serving can be handled by Herd.

```bash
# 1. Install PHP dependencies
composer install

# 2. Copy the environment file
cp .env.example .env

# 3. Generate an application key
php artisan key:generate

# 4. Run database migrations
php artisan migrate

# 5. Add your OpenAI key for AI roadmap generation in .env
OPENAI_API_KEY=

# 6. Install JavaScript dependencies
npm install

# 7. Start the development server
composer dev
```

If using Herd, the app may also be available at:

```text
http://php_project.test
```

---

## Verification

```bash
npm run build
composer test
```

Current verification after the AI integration check:

- `npm run build` passes.
- Pest/Laravel tests cover the AI roadmap bridge, partial stage failures, and fallback path.
- `composer test` requires PHP and Composer to be available on your shell PATH.

---

## Notes

- The app no longer uses Blade page templates for the UI. Blade is only used for the single Inertia root view.
- The project keeps Laravel session auth, CSRF protection, redirects, validation, and middleware.
- AI roadmap generation uses LangChain.js from a server-side Node bridge. It runs separate prompt stages for the profile, core features, MVP scope, and checklist with a default 120 second process timeout. If `OPENAI_API_KEY` is missing, the app stores a local fallback roadmap. If one AI stage fails, that section stores a visible failure message while successful sections are preserved and logged.
- Action Plan tasks are currently generated from local fallback data, not the AI bridge. They are stored on each idea as JSON in `action_tasks` and normalized into categories, phases, and statuses for the UI.
- No separate Next.js app, token-based auth, or public API layer has been introduced.
- The current UI is a restrained dark product interface using Tailwind CSS and `lucide-react` icons.

---

## Project Docs

For more detailed technical information, see:

- [TECH_STACK.md](./TECH_STACK.md) - Technology, libraries, and tooling
- [PROJECT_STRUCTURE.md](./PROJECT_STRUCTURE.md) - File and folder map
- [progress.md](./progress.md) - Feature implementation history

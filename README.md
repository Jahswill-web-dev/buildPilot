# BuildPilot - Project Documentation

A Laravel founder-planning app with an Inertia React frontend.

---

## What Is This Project?

BuildPilot is a founder workspace for capturing startup ideas, turning them into AI-assisted roadmaps, and tracking the next steps for each idea.

The backend is Laravel. The frontend is now React through Inertia, which means Laravel still owns routing, sessions, validation, authorization, and database work, while React owns the interactive browser UI.

---

## What It Does Right Now

- **Register, sign in, and log out** using Laravel session authentication.
- **Visit a public landing page** at `/` that explains the product, shows static UI previews, and links to account creation.
- **Create ideas** with a name and description.
- **Generate startup roadmaps with AI in the background** when new ideas are saved, including a target user profile, problem statement, desired outcome, core features, MVP scope, roadmap phases, and editable checklist.
- **See generation progress on the dashboard** with a skeleton loading card and rotating progress text while the queued AI job runs.
- **Review an Action Plan** with AI-generated phases and local placeholder tasks for now. Tasks still keep product, marketing, and validation categories.
- **Browse roadmap phases first**, then open a phase page that can contain tasks from multiple categories.
- **Open task details** from phase pages and toggle task status between pending and completed.
- **View your own ideas only** on the authenticated dashboard.
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
| **Landing (`/`)** | Public founder-workspace landing page with product feature copy, static UI previews, and account CTAs |
| **Dashboard (`/ideas`)** | Auth-protected idea creation form, saved idea list, and generating-roadmap skeleton cards |
| **Idea detail (`/ideas/{idea}`)** | Editable idea details, generated roadmap sections, Action Plan preview, and checklist workflow |
| **Action Plan (`/ideas/{idea}/tasks`)** | Roadmap phase cards with progress counts and links into phase task boards |
| **Global task phase (`/ideas/{idea}/tasks/phases/{phaseSlug}`)** | Phase description, goal, success criteria, and pending/completed tasks across categories |
| **Task phase (`/ideas/{idea}/tasks/{category}/{phaseSlug}`)** | Compatibility route for one category/phase task board |
| **Task detail (`/ideas/{idea}/tasks/items/{taskId}`)** | Detailed task guidance with editable status and task fields |
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
- `resources/js/hooks/` - small React hooks such as the generating-idea polling hook.
- `resources/css/app.css` - Tailwind CSS v4 entry point.

Important backend files:

- `routes/web.php` - named web routes.
- `app/Http/Controllers/AuthController.php` - registration, login, logout.
- `app/Http/Controllers/IdeaController.php` - idea list/create/show/update/delete.
- `app/Http/Controllers/IdeaTaskController.php` - Action Plan overview, phase pages, and task status updates.
- `app/Http/Controllers/ChecklistItemController.php` - checklist item create/update/delete.
- `app/Jobs/GenerateIdeaRoadmap.php` - queued job that runs AI roadmap generation and updates idea state.
- `app/Services/ActionPhases/ActionPhases.php` - AI roadmap phase normalization, slugs, category validation, and fallback phases.
- `app/Services/ActionTasks/ActionTasks.php` - Action Plan fallback tasks, categories, phases, and normalization logic.
- `app/Services/Ai/RoadmapGenerator.php` - Laravel service that calls the LangChain roadmap bridge.
- `app/Services/Checklists/ChecklistItems.php` - checklist fallback and normalization logic.
- `app/Services/CoreFeatures/CoreFeatures.php` - core feature fallback and normalization logic.
- `app/Services/MvpScopes/MvpScope.php` - MVP scope fallback and normalization logic.
- `app/Services/TargetUsers/TargetUserProfile.php` - target user fallback and normalization logic.
- `resources/js/ai/generate-roadmap.mjs` - server-side LangChain/OpenAI bridge used by the queued roadmap generation job.
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

# 7. Start the development server, queue worker, and Vite
composer dev
```

The AI roadmap job is queued. If you are not using `composer dev`, run a queue worker in a separate terminal:

```bash
php artisan queue:work
```

Without a running worker, new ideas will stay in the `generating` state.

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
- The public `/` route is a marketing/landing page. The authenticated dashboard lives at `/ideas`, while the route name `home` still points to the dashboard for post-login redirects.
- AI roadmap generation uses a queued Laravel job plus a server-side LangChain.js Node bridge. The bridge returns structured profile, core features, MVP scope, phases, and checklist data. If `OPENAI_API_KEY` is missing or generation fails, the app stores local fallback roadmap data.
- Roadmap phases are AI-generated and stored in `action_phases`. They include title, description, goal, success criteria, order, primary category, and included categories.
- Action Plan tasks are currently generated from local fallback data, not the AI bridge. They are stored on each idea as JSON in `action_tasks` and normalized into categories, phases, and statuses for the UI. Future AI task generation is expected to generate categorized tasks per phase.
- No separate Next.js app, token-based auth, or public API layer has been introduced.
- The current UI is a restrained dark product interface using Tailwind CSS and `lucide-react` icons.

---

## Project Docs

For more detailed technical information, see:

- [TECH_STACK.md](./TECH_STACK.md) - Technology, libraries, and tooling
- [PROJECT_STRUCTURE.md](./PROJECT_STRUCTURE.md) - File and folder map
- [progress.md](./progress.md) - Feature implementation history

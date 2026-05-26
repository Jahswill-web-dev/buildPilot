# Tech Stack

A detailed breakdown of the technologies, tools, and libraries used in this project.

---

## Backend

### PHP `^8.3`
The server-side language powering the Laravel application.

### Laravel `^13.8`
The core PHP framework. Laravel provides routing, controllers, middleware, Eloquent ORM, validation, session authentication, Blade, artisan commands, and the service container.

### Inertia Laravel `^3.1`
The server-side Inertia adapter. Controllers return Inertia responses such as `Inertia::render('Ideas/Index', [...])` instead of returning Blade pages directly.

### Laravel Tinker `^3.0`
An interactive REPL for inspecting models, running queries, and testing application behavior from the command line.

### Symfony Process
Laravel uses Symfony Process internally. This project uses it to call the server-side Node/LangChain roadmap generator from `App\Services\Ai\RoadmapGenerator`.

---

## Database

### SQLite
The project is configured to use SQLite with the database file at `database/database.sqlite`.

### Laravel Eloquent ORM
Eloquent maps PHP model classes to database tables. The project currently uses:

- `User` - authenticated users.
- `Idea` - user-owned idea records.

The `Idea` model stores an idea name, description, status, owner, generated roadmap sections, JSON checklist, and JSON Action Plan tasks. JSON roadmap, checklist, and task columns are cast to PHP arrays in `app/Models/Idea.php`.

### Laravel Migrations
Database schema is managed with migrations. The ideas table has evolved to include:

- Initial idea storage with `description`, `state`, and timestamps.
- User ownership via `user_id`.
- Idea detail support via `name`.
- Editable checklist storage via JSON checklist items.
- Generated target user, core features, and MVP scope storage via JSON columns.
- Action Plan task storage via the `action_tasks` JSON column.

---

## Frontend

### Inertia + React
The frontend is now a React application mounted through Inertia. Laravel still owns web routes, auth, validation, authorization, redirects, and persistence. React owns page rendering and interactive UI behavior.

Current React page components live in:

```text
resources/js/Pages/
```

Current shared React components live in:

```text
resources/js/Components/
```

The single Blade view still in active use is:

```text
resources/views/app.blade.php
```

It provides the HTML shell for Inertia and loads Vite assets.

### React `^19.2`
React powers the client-side page components, forms, inline editing, checklist interactions, Action Plan category/phase navigation, task modals, and reusable UI components.

### `@inertiajs/react` `^3.2`
The React adapter for Inertia. It provides:

- `createInertiaApp`
- `Head`
- `Link`
- `router`
- `useForm`
- `usePage`

### Tailwind CSS `^4.0`
The app uses Tailwind CSS v4 through the official Vite plugin. Utility classes are used in React components and the Inertia root view.

### `lucide-react` `^1.16`
Icon library used for navigation, auth screens, idea cards, checklist actions, Action Plan cards/modals, and static pages.

### Instrument Sans via Bunny Fonts
The primary font is loaded through the Laravel Vite plugin's `bunny()` helper and configured in `resources/css/app.css`.

---

## Build Tooling

### Vite `^8.0`
Vite handles frontend development, hot module replacement, and production builds.

### Laravel Vite Plugin `^3.1`
Connects Laravel and Vite. The app uses `@vite` in the Inertia root Blade view to load `resources/css/app.css` and `resources/js/app.js`.

### `@vitejs/plugin-react` `^6.0`
Adds React support to Vite.

### `@tailwindcss/vite` `^4.0`
Runs Tailwind CSS through Vite without a separate PostCSS setup.

### Axios `^1.16`
HTTP client used by Inertia's browser-side request adapter.

### LangChain.js + OpenAI
New idea roadmaps are generated through a staged Node bridge at `resources/js/ai/generate-roadmap.mjs`. It uses `@langchain/openai`, `@langchain/core`, and `zod` to request structured roadmap sections from OpenAI. Prompt text lives in `resources/prompts/roadmap/`, with separate prompts for the profile, core features, MVP scope, and checklist stages.

### Concurrently `^9.0`
Used by the `composer dev` script to run Laravel, the queue worker, and Vite together.

---

## Development & Testing Tools

### Pest PHP `^4.7` + `pestphp/pest-plugin-laravel` `^4.1`
The primary test framework. Feature tests cover authentication, idea ownership, idea creation, checklist persistence, detail page access, authorization failures, and Inertia page responses.

### Laravel Pint `^1.27`
Laravel's PHP code formatter.

### Laravel Pail `^1.2`
Real-time Laravel log tailing.

### FakerPHP / Faker `^1.23`
Used by factories for test data.

### Mockery `^1.6`
Mocking library used by the Laravel/Pest testing stack.

### NunoMaduro Collision `^8.6`
Improves terminal error output for tests and artisan commands.

---

## Environment & Configuration

| Variable | Value | Purpose |
|---|---|---|
| `APP_ENV` | `local` | Local development mode |
| `DB_CONNECTION` | `sqlite` | SQLite database driver |
| `SESSION_DRIVER` | `database` | Database-backed sessions |
| `CACHE_STORE` | `database` | Database-backed cache |
| `QUEUE_CONNECTION` | `database` | Database-backed queue jobs |
| `MAIL_MAILER` | `log` | Logs outgoing mail locally |
| `OPENAI_API_KEY` | empty by default | Enables AI roadmap generation |
| `OPENAI_CHECKLIST_MODEL` | `gpt-5-nano` | Default low-cost OpenAI model for roadmap generation |
| `AI_ROADMAP_TIMEOUT_SECONDS` | `120` | Timeout for the staged server-side LangChain bridge |
| `AI_NODE_BINARY` | `node` | Node executable used by Laravel's roadmap bridge |
| `AI_ROADMAP_SCRIPT` | `resources/js/ai/generate-roadmap.mjs` | Optional override for the roadmap bridge script, mainly useful in tests |

---

## Runtime Environment

### Laravel Herd
The project lives under `C:\Users\ISAAC ONUEGBU\Herd\`, so it is intended to run well with Laravel Herd on Windows.

### Node.js / npm
npm is used for JavaScript dependencies and Vite scripts.

---

## Version Control

The project includes Git configuration files such as `.gitignore`, `.gitattributes`, and `.editorconfig`.

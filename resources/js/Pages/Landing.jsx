import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    CheckCircle2,
    ClipboardCheck,
    Layers3,
    Lightbulb,
    ListChecks,
    LockKeyhole,
    PencilLine,
    Route,
    Sparkles,
    Target,
    Users,
} from 'lucide-react';
import AppLayout from '../Components/AppLayout';

const featureHighlights = [
    {
        icon: Lightbulb,
        title: 'Capture raw ideas',
        text: 'Save the name and messy first description before the signal gets lost.',
    },
    {
        icon: Sparkles,
        title: 'Generate founder context',
        text: 'Turn the idea into target-user insight, a problem statement, and a desired outcome.',
    },
    {
        icon: PencilLine,
        title: 'Edit the strategy inline',
        text: 'Refine generated sections in place as your thinking gets sharper.',
    },
    {
        icon: Layers3,
        title: 'Scope the MVP',
        text: 'Separate must-have work from nice-to-have ideas and later bets.',
    },
    {
        icon: Route,
        title: 'Plan by phase',
        text: 'Break the work into product, marketing, and validation phases.',
    },
    {
        icon: LockKeyhole,
        title: 'Keep it private',
        text: 'Each account only sees its own ideas, roadmaps, and execution tasks.',
    },
];

const phaseCards = [
    {
        title: 'Validate the market',
        description: 'Interview likely users, test the pain, and define the first wedge.',
        goal: 'Find the buying trigger before building too much.',
        tasks: 8,
        done: 3,
        open: 5,
        categories: ['Validation', 'Marketing'],
    },
    {
        title: 'Shape the MVP',
        description: 'Choose the smallest useful workflow and remove distracting extras.',
        goal: 'Make the first version narrow enough to ship.',
        tasks: 6,
        done: 2,
        open: 4,
        categories: ['Product'],
    },
    {
        title: 'Launch the first loop',
        description: 'Package the offer, publish the first channel, and track real responses.',
        goal: 'Create a repeatable path from interest to feedback.',
        tasks: 7,
        done: 1,
        open: 6,
        categories: ['Marketing', 'Validation'],
    },
];

const pendingTasks = [
    'Interview 5 solo founders about roadmap confusion',
    'Draft onboarding flow for first saved idea',
    'Write validation questions for landing CTA traffic',
];

const completedTasks = [
    'Define target user profile',
    'Choose must-have MVP workflow',
];

export default function Landing() {
    const { auth } = usePage().props;
    const isSignedIn = Boolean(auth.user);
    const primaryHref = isSignedIn ? '/ideas' : '/register';
    const primaryLabel = isSignedIn ? 'Open dashboard' : 'Create account';

    return (
        <AppLayout maxWidth="max-w-7xl">
            <Head title="Founder Workspace" />

            <section className="grid min-h-[calc(100vh-7rem)] items-center gap-10 py-8 lg:grid-cols-[0.92fr_1.08fr] lg:py-12">
                <div className="min-w-0">
                    <div className="mb-5 inline-flex items-center gap-2 rounded-full border border-teal-400/20 bg-teal-400/10 px-3 py-1 text-xs font-semibold uppercase text-teal-200">
                        <Sparkles className="h-3.5 w-3.5" aria-hidden="true" />
                        Founder workspace
                    </div>

                    <h1 className="max-w-3xl text-4xl font-semibold leading-tight text-white sm:text-5xl lg:text-6xl">
                        Turn a rough startup idea into a plan you can execute.
                    </h1>

                    <p className="mt-5 max-w-2xl text-base leading-7 text-zinc-300 sm:text-lg">
                        BuildPilot captures the first spark, generates the strategic shape around it, and turns the work into roadmap phases, MVP scope, and trackable tasks.
                    </p>

                    <div className="mt-7 flex flex-col gap-3 sm:flex-row">
                        <Link
                            href={primaryHref}
                            id="landing-primary-cta"
                            className="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-teal-950/40 transition hover:bg-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-zinc-950"
                        >
                            {primaryLabel}
                            <ArrowRight className="h-4 w-4" aria-hidden="true" />
                        </Link>
                        {!isSignedIn ? (
                            <Link
                                href="/login"
                                className="inline-flex items-center justify-center rounded-lg border border-white/10 bg-white/[0.04] px-5 py-3 text-sm font-semibold text-zinc-100 transition hover:border-white/20 hover:bg-white/[0.07] focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-zinc-950"
                            >
                                Sign in
                            </Link>
                        ) : null}
                    </div>

                    <div className="mt-8 hidden gap-3 text-sm text-zinc-400 sm:grid sm:grid-cols-3">
                        <ProofPoint label="Roadmaps" value="AI-generated" />
                        <ProofPoint label="Scope" value="MVP-ready" />
                        <ProofPoint label="Tasks" value="Trackable" />
                    </div>
                </div>

                <HeroProductMockup />
            </section>

            <section className="border-t border-white/10 py-14">
                <div className="mb-8 max-w-2xl">
                    <p className="text-xs font-semibold uppercase tracking-widest text-teal-300">Product flow</p>
                    <h2 className="mt-3 text-2xl font-semibold text-white sm:text-3xl">
                        From idea capture to execution without leaving the workspace.
                    </h2>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    {featureHighlights.map((feature) => (
                        <FeatureCard key={feature.title} feature={feature} />
                    ))}
                </div>
            </section>

            <section className="grid gap-6 border-t border-white/10 py-14 lg:grid-cols-[0.82fr_1.18fr]">
                <div className="min-w-0">
                    <p className="text-xs font-semibold uppercase tracking-widest text-teal-300">Generated roadmap</p>
                    <h2 className="mt-3 text-2xl font-semibold text-white sm:text-3xl">
                        Founder-grade clarity for the parts that usually stay fuzzy.
                    </h2>
                    <p className="mt-4 text-sm leading-6 text-zinc-400">
                        The generated workspace gives each idea a target user, problem, desired outcome, core features, and a practical MVP boundary. Every section stays editable.
                    </p>
                </div>

                <RoadmapMockup />
            </section>

            <section className="border-t border-white/10 py-14">
                <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="max-w-2xl">
                        <p className="text-xs font-semibold uppercase tracking-widest text-teal-300">Action plan</p>
                        <h2 className="mt-3 text-2xl font-semibold text-white sm:text-3xl">
                            Convert strategy into phases, tasks, and progress.
                        </h2>
                    </div>
                    <div className="rounded-lg border border-white/10 bg-white/[0.04] px-4 py-3">
                        <p className="text-sm font-semibold text-white">6 of 13</p>
                        <p className="text-xs text-zinc-500">sample tasks complete</p>
                    </div>
                </div>

                <div className="grid gap-5 xl:grid-cols-[1fr_0.95fr]">
                    <PhaseGrid />
                    <TaskBoardMockup />
                </div>
            </section>

            <section className="border-t border-white/10 py-14">
                <div className="rounded-lg border border-teal-400/20 bg-teal-400/[0.07] px-6 py-8 text-center sm:px-10">
                    <h2 className="text-2xl font-semibold text-white sm:text-3xl">
                        Start with one idea. Leave with a founder-ready plan.
                    </h2>
                    <p className="mx-auto mt-3 max-w-2xl text-sm leading-6 text-zinc-300">
                        Create an account, capture your first idea, and let BuildPilot shape the strategy and next actions around it.
                    </p>
                    <div className="mt-6 flex justify-center">
                        <Link
                            href={primaryHref}
                            className="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-teal-950/40 transition hover:bg-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-zinc-950"
                        >
                            {primaryLabel}
                            <ArrowRight className="h-4 w-4" aria-hidden="true" />
                        </Link>
                    </div>
                </div>
            </section>
        </AppLayout>
    );
}

function ProofPoint({ label, value }) {
    return (
        <div className="rounded-lg border border-white/10 bg-white/[0.035] px-4 py-3">
            <p className="text-sm font-semibold text-white">{value}</p>
            <p className="mt-1 text-xs text-zinc-500">{label}</p>
        </div>
    );
}

function FeatureCard({ feature }) {
    const Icon = feature.icon;

    return (
        <article className="rounded-lg border border-white/10 bg-white/[0.035] p-5 transition hover:border-teal-500/30 hover:bg-white/[0.055]">
            <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-lg border border-teal-400/20 bg-teal-400/10 text-teal-200">
                <Icon className="h-5 w-5" aria-hidden="true" />
            </div>
            <h3 className="text-base font-semibold text-white">{feature.title}</h3>
            <p className="mt-2 text-sm leading-6 text-zinc-400">{feature.text}</p>
        </article>
    );
}

function HeroProductMockup() {
    return (
        <div className="min-w-0 rounded-lg border border-white/10 bg-zinc-950/80 shadow-2xl shadow-black/40">
            <div className="flex items-center justify-between border-b border-white/10 px-4 py-3">
                <div className="flex items-center gap-2">
                    <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600">
                        <Lightbulb className="h-4 w-4 text-white" aria-hidden="true" />
                    </span>
                    <span className="text-sm font-semibold text-white">BuildPilot</span>
                </div>
                <span className="rounded-full bg-teal-500/15 px-2 py-0.5 text-xs font-medium text-teal-200">
                    Generating
                </span>
            </div>

            <div className="grid gap-4 p-4 md:grid-cols-[0.95fr_1.05fr]">
                <div className="rounded-lg border border-white/10 bg-white/[0.035] p-4">
                    <p className="text-xs font-semibold uppercase tracking-widest text-zinc-500">New idea</p>
                    <div className="mt-4 space-y-4">
                        <MockField label="Idea name" value="Founder launch planner" />
                        <MockField
                            label="Description"
                            value="A workspace that turns a raw startup idea into the customer, scope, roadmap, and tasks needed to start validating."
                            multiline
                        />
                        <div className="flex justify-end">
                            <span className="rounded-lg bg-teal-600 px-3 py-2 text-xs font-semibold text-white">
                                Generate Roadmap
                            </span>
                        </div>
                    </div>
                </div>

                <div className="space-y-3">
                    <div className="rounded-lg border border-white/10 bg-white/[0.04] p-4">
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <h3 className="break-words text-sm font-semibold text-white">Founder launch planner</h3>
                                <p className="mt-1 line-clamp-3 text-sm leading-6 text-zinc-400">
                                    Build the first plan around target users, scope, and validation.
                                </p>
                            </div>
                            <span className="flex h-8 w-8 items-center justify-center rounded-lg border border-white/10 bg-white/5 text-zinc-400">
                                <ArrowRight className="h-4 w-4" aria-hidden="true" />
                            </span>
                        </div>
                        <div className="mt-3 flex flex-wrap items-center gap-3">
                            <span className="text-xs text-zinc-500">Just now</span>
                            <span className="rounded-full bg-teal-500/15 px-2 py-0.5 text-xs font-medium text-teal-200">
                                Generating
                            </span>
                        </div>
                    </div>

                    <div className="rounded-lg border border-white/10 bg-zinc-950/55 p-4">
                        <div className="mb-3 flex items-center gap-2 text-teal-200">
                            <Sparkles className="h-4 w-4" aria-hidden="true" />
                            <p className="text-sm font-semibold">Building roadmap sections</p>
                        </div>
                        <div className="space-y-2">
                            {['Target user profile', 'Core features', 'MVP scope'].map((item) => (
                                <div key={item} className="flex items-center gap-2 text-sm text-zinc-400">
                                    <CheckCircle2 className="h-4 w-4 text-teal-300" aria-hidden="true" />
                                    {item}
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

function MockField({ label, value, multiline = false }) {
    return (
        <div>
            <p className="mb-1 text-sm font-medium text-zinc-300">{label}</p>
            <div className={`rounded-lg border border-white/10 bg-zinc-950/70 px-3 py-2 text-sm leading-6 text-zinc-300 ${multiline ? 'min-h-24' : ''}`}>
                {value}
            </div>
        </div>
    );
}

function RoadmapMockup() {
    return (
        <div className="rounded-lg border border-white/10 bg-white/[0.035] p-5">
            <div className="mb-5 flex flex-wrap items-center gap-3">
                <span className="rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs font-medium text-emerald-300">
                    Done
                </span>
                <span className="text-xs text-zinc-500">Founder launch planner</span>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                <RoadmapPanel
                    icon={Users}
                    title="Target User"
                    items={[
                        ['User type', 'Solo founders validating their first SaaS workflow.'],
                        ['Main problem', 'They know the idea but not the next practical move.'],
                    ]}
                />
                <RoadmapPanel
                    icon={Target}
                    title="Problem Statement"
                    text="Early founders lose momentum because strategy, validation, and execution live in separate notes."
                />
                <RoadmapPanel
                    icon={ClipboardCheck}
                    title="Core Features + User Flow"
                    items={[
                        ['Capture', 'Save an idea and context in one place.'],
                        ['Generate', 'Create roadmap sections from the idea brief.'],
                        ['Track', 'Move tasks from pending to completed.'],
                    ]}
                />
                <RoadmapPanel
                    icon={ListChecks}
                    title="MVP Scope"
                    columns={[
                        ['Must-have', ['Idea intake', 'Roadmap generation', 'Task tracking']],
                        ['Later', ['Team sharing', 'Launch analytics']],
                    ]}
                />
            </div>
        </div>
    );
}

function RoadmapPanel({ icon: Icon, title, text, items, columns }) {
    return (
        <section className="min-w-0 rounded-lg border border-white/10 bg-zinc-950/45 p-4">
            <div className="mb-3 flex items-center gap-2 text-teal-200">
                <Icon className="h-4 w-4" aria-hidden="true" />
                <h3 className="text-sm font-semibold uppercase tracking-widest text-zinc-500">{title}</h3>
            </div>

            {text ? (
                <p className="break-words text-sm leading-6 text-zinc-300">{text}</p>
            ) : null}

            {items ? (
                <div className="space-y-3">
                    {items.map(([label, value]) => (
                        <div key={label}>
                            <p className="text-xs font-semibold uppercase text-zinc-500">{label}</p>
                            <p className="mt-1 break-words text-sm leading-6 text-zinc-300">{value}</p>
                        </div>
                    ))}
                </div>
            ) : null}

            {columns ? (
                <div className="grid gap-4 sm:grid-cols-2">
                    {columns.map(([label, values]) => (
                        <div key={label}>
                            <p className="mb-2 text-xs font-semibold uppercase text-zinc-500">{label}</p>
                            <ul className="space-y-2">
                                {values.map((value) => (
                                    <li key={value} className="text-sm leading-5 text-zinc-300">
                                        {value}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>
            ) : null}
        </section>
    );
}

function PhaseGrid() {
    return (
        <div className="grid gap-4 md:grid-cols-3 xl:grid-cols-1">
            {phaseCards.map((phase) => (
                <article key={phase.title} className="rounded-lg border border-white/10 bg-white/[0.035] p-5">
                    <div className="flex items-start justify-between gap-4">
                        <div className="min-w-0">
                            <p className="text-xs font-semibold uppercase tracking-widest text-zinc-500">Phase</p>
                            <h3 className="mt-2 break-words text-base font-semibold text-white">{phase.title}</h3>
                        </div>
                        <span className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg border border-white/10 bg-white/5 text-zinc-400">
                            <ArrowRight className="h-4 w-4" aria-hidden="true" />
                        </span>
                    </div>
                    <p className="mt-4 line-clamp-3 text-sm leading-6 text-zinc-400">{phase.description}</p>
                    <p className="mt-3 text-sm font-medium leading-6 text-teal-100">{phase.goal}</p>
                    <div className="mt-4 flex flex-wrap gap-2">
                        {phase.categories.map((category) => (
                            <span key={`${phase.title}-${category}`} className="rounded-full border border-white/10 bg-white/5 px-2 py-0.5 text-xs font-medium text-zinc-400">
                                {category}
                            </span>
                        ))}
                    </div>
                    <div className="mt-5 grid grid-cols-3 gap-2">
                        <PhaseStat label="Tasks" value={phase.tasks} />
                        <PhaseStat label="Done" value={phase.done} />
                        <PhaseStat label="Open" value={phase.open} />
                    </div>
                </article>
            ))}
        </div>
    );
}

function PhaseStat({ label, value }) {
    return (
        <div className="rounded-lg border border-white/10 bg-zinc-950/45 px-3 py-2">
            <p className="text-sm font-semibold text-white">{value}</p>
            <p className="text-xs text-zinc-500">{label}</p>
        </div>
    );
}

function TaskBoardMockup() {
    return (
        <div className="grid gap-4 lg:grid-cols-2">
            <TaskColumn title="Pending" tasks={pendingTasks} count={pendingTasks.length} />
            <TaskColumn title="Completed" tasks={completedTasks} count={completedTasks.length} completed />
        </div>
    );
}

function TaskColumn({ title, tasks, count, completed = false }) {
    return (
        <div className="min-w-0 rounded-lg border border-white/10 bg-white/[0.025] p-4">
            <div className="mb-4 flex items-center justify-between gap-3">
                <h3 className="text-sm font-semibold uppercase tracking-widest text-zinc-500">{title}</h3>
                <span className="rounded-full border border-white/10 bg-white/5 px-2 py-0.5 text-xs font-medium text-zinc-400">
                    {count}
                </span>
            </div>

            <div className="grid gap-3">
                {tasks.map((task) => (
                    <div key={task} className="rounded-lg border border-white/10 bg-zinc-950/55 p-3">
                        <div className="flex items-start gap-3">
                            <span className={`mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full border ${completed ? 'border-emerald-400/40 bg-emerald-400/15 text-emerald-300' : 'border-white/15 bg-white/5 text-zinc-500'}`}>
                                {completed ? <CheckCircle2 className="h-3.5 w-3.5" aria-hidden="true" /> : null}
                            </span>
                            <p className={`break-words text-sm leading-6 ${completed ? 'text-zinc-400 line-through decoration-zinc-600' : 'text-zinc-200'}`}>
                                {task}
                            </p>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

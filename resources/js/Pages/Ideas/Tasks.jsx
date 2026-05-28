import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ChevronRight } from 'lucide-react';
import { useMemo } from 'react';
import { taskProgress } from '../../Components/ActionTaskRow';
import AppLayout from '../../Components/AppLayout';

const categoryLabels = {
    product: 'Product',
    marketing: 'Marketing',
    validation: 'Validation',
};

export default function Tasks({ idea }) {
    const progress = taskProgress(idea.actionTasks);
    const phases = useMemo(
        () => attachTaskCountsToPhases(idea.actionPhases ?? [], idea.actionTasks),
        [idea.actionPhases, idea.actionTasks],
    );

    return (
        <AppLayout maxWidth="max-w-6xl">
            <Head title={`Action Plan - ${idea.name}`} />

            <div className="mb-6">
                <Link
                    href={`/ideas/${idea.id}`}
                    className="inline-flex items-center gap-2 rounded-lg px-2 py-1 text-sm text-zinc-400 transition hover:bg-white/5 hover:text-white focus:outline-none focus:ring-2 focus:ring-teal-500"
                >
                    <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                    Back to idea
                </Link>
            </div>

            <header className="border-b border-white/10 pb-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="min-w-0">
                        <p className="text-xs font-semibold uppercase tracking-widest text-zinc-500">Action Plan</p>
                        <h1 className="mt-2 break-words text-2xl font-semibold text-white">{idea.name}</h1>
                        <p className="mt-2 max-w-2xl break-words text-sm leading-6 text-zinc-400">
                            {idea.description}
                        </p>
                    </div>
                    <div className="flex-shrink-0 rounded-lg border border-white/10 bg-white/[0.04] px-4 py-3">
                        <p className="text-sm font-semibold text-white">{progress.completed} of {progress.total}</p>
                        <p className="text-xs text-zinc-500">tasks complete</p>
                    </div>
                </div>
            </header>

            <section className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {phases.map((phase) => (
                    <Link
                        key={phase.slug}
                        href={`/ideas/${idea.id}/tasks/phases/${phase.slug}`}
                        className="group rounded-lg border border-white/10 bg-white/[0.035] p-5 transition hover:border-teal-500/40 hover:bg-white/[0.055] focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-zinc-950"
                    >
                        <div className="flex items-start justify-between gap-4">
                            <div className="min-w-0">
                                <p className="text-xs font-semibold uppercase tracking-widest text-zinc-500">Phase</p>
                                <h2 className="mt-2 break-words text-lg font-semibold text-white transition group-hover:text-teal-100">
                                    {phase.title}
                                </h2>
                            </div>
                            <span className="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg border border-white/10 bg-white/5 text-zinc-400 transition group-hover:border-teal-500/40 group-hover:text-teal-200">
                                <ChevronRight className="h-4 w-4" aria-hidden="true" />
                            </span>
                        </div>

                        {phase.description ? (
                            <p className="mt-4 line-clamp-3 break-words text-sm leading-6 text-zinc-400">
                                {phase.description}
                            </p>
                        ) : null}

                        {phase.goal ? (
                            <p className="mt-3 break-words text-sm font-medium leading-6 text-teal-100">
                                {phase.goal}
                            </p>
                        ) : null}

                        <div className="mt-4 flex flex-wrap gap-2">
                            {phase.includedCategories.map((category) => (
                                <span
                                    key={`${phase.slug}-${category}`}
                                    className="rounded-full border border-white/10 bg-white/5 px-2 py-0.5 text-xs font-medium text-zinc-400"
                                >
                                    {categoryLabels[category] ?? 'Product'}
                                </span>
                            ))}
                        </div>

                        <div className="mt-5 grid grid-cols-3 gap-2">
                            <PhaseStat label="Tasks" value={phase.total} />
                            <PhaseStat label="Done" value={phase.completed} />
                            <PhaseStat label="Open" value={phase.pending} />
                        </div>
                    </Link>
                ))}
            </section>
        </AppLayout>
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

function attachTaskCountsToPhases(phases, tasks) {
    return phases.map((phase) => {
        const phaseTasks = tasks.filter((task) => task.phaseSlug === phase.slug);

        return {
            ...phase,
            total: phaseTasks.length,
            completed: phaseTasks.filter((task) => task.status === 'completed').length,
            pending: phaseTasks.filter((task) => task.status === 'pending').length,
        };
    });
}

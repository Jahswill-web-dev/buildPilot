import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ChevronRight } from 'lucide-react';
import { useMemo, useState } from 'react';
import ActionTaskModal from '../../Components/ActionTaskModal';
import ActionTaskRow, { taskProgress } from '../../Components/ActionTaskRow';
import AppLayout from '../../Components/AppLayout';

const tabs = [
    { label: 'Phases', value: 'phases' },
    { label: 'Product tasks', value: 'product' },
    { label: 'Marketing tasks', value: 'marketing' },
    { label: 'Market validation', value: 'validation' },
];

const columns = [
    { title: 'Pending', status: 'pending' },
    { title: 'Completed', status: 'completed' },
];

const categoryLabels = {
    product: 'Product',
    marketing: 'Marketing',
    validation: 'Validation',
};

export default function Tasks({ idea }) {
    const [activeTab, setActiveTab] = useState('phases');
    const [selectedTask, setSelectedTask] = useState(null);
    const progress = taskProgress(idea.actionTasks);
    const phases = useMemo(
        () => attachTaskCountsToPhases(idea.actionPhases ?? [], idea.actionTasks),
        [idea.actionPhases, idea.actionTasks],
    );
    const activeTasks = useMemo(
        () => idea.actionTasks.filter((task) => (task.category ?? 'product') === activeTab),
        [activeTab, idea.actionTasks],
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

            <div className="mt-6 flex flex-wrap gap-2 border-b border-white/10">
                {tabs.map((tab) => {
                    const count = tab.value === 'phases'
                        ? phases.length
                        : idea.actionTasks.filter((task) => (task.category ?? 'product') === tab.value).length;
                    const isActive = activeTab === tab.value;

                    return (
                        <button
                            key={tab.value}
                            type="button"
                            onClick={() => setActiveTab(tab.value)}
                            className={`-mb-px inline-flex items-center gap-2 border-b-2 px-3 py-3 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-teal-500 ${isActive ? 'border-teal-400 text-white' : 'border-transparent text-zinc-500 hover:text-zinc-200'}`}
                        >
                            {tab.label}
                            <span className={`rounded-full px-2 py-0.5 text-xs ${isActive ? 'bg-teal-500/15 text-teal-200' : 'bg-white/5 text-zinc-500'}`}>
                                {count}
                            </span>
                        </button>
                    );
                })}
            </div>

            {activeTab === 'phases' ? (
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
            ) : (
                <TaskColumns
                    ideaId={idea.id}
                    tasks={activeTasks}
                    onOpenTask={setSelectedTask}
                />
            )}

            {selectedTask ? (
                <ActionTaskModal ideaId={idea.id} task={selectedTask} onClose={() => setSelectedTask(null)} />
            ) : null}
        </AppLayout>
    );
}

function TaskColumns({ ideaId, tasks, onOpenTask }) {
    return (
        <section className="mt-6 grid gap-4 lg:grid-cols-2">
            {columns.map((column) => {
                const columnTasks = tasks.filter((task) => task.status === column.status);

                return (
                    <div key={column.status} className="min-w-0 rounded-lg border border-white/10 bg-white/[0.025] p-4">
                        <div className="mb-4 flex items-center justify-between gap-3">
                            <h2 className="text-sm font-semibold uppercase tracking-widest text-zinc-500">
                                {column.title}
                            </h2>
                            <span className="rounded-full border border-white/10 bg-white/5 px-2 py-0.5 text-xs font-medium text-zinc-400">
                                {columnTasks.length}
                            </span>
                        </div>

                        {columnTasks.length ? (
                            <div className="grid gap-3">
                                {columnTasks.map((task) => (
                                    <ActionTaskRow
                                        key={task.id}
                                        ideaId={ideaId}
                                        task={task}
                                        onOpen={onOpenTask}
                                    />
                                ))}
                            </div>
                        ) : (
                            <div className="rounded-lg border border-dashed border-white/10 px-4 py-8 text-center text-sm text-zinc-500">
                                No tasks here.
                            </div>
                        )}
                    </div>
                );
            })}
        </section>
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

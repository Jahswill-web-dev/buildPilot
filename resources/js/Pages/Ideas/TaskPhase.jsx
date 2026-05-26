import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import ActionTaskModal from '../../Components/ActionTaskModal';
import ActionTaskRow, { taskProgress } from '../../Components/ActionTaskRow';
import AppLayout from '../../Components/AppLayout';

const columns = [
    { title: 'Pending', status: 'pending' },
    { title: 'Completed', status: 'completed' },
];

const categoryLabels = {
    product: 'Product tasks',
    marketing: 'Marketing tasks',
    validation: 'Market validation',
};

export default function TaskPhase({ idea, category, phase }) {
    const [selectedTask, setSelectedTask] = useState(null);
    const progress = taskProgress(phase.tasks);

    return (
        <AppLayout maxWidth="max-w-6xl">
            <Head title={`${phase.name} - ${idea.name}`} />

            <div className="mb-6">
                <Link
                    href={`/ideas/${idea.id}/tasks`}
                    className="inline-flex items-center gap-2 rounded-lg px-2 py-1 text-sm text-zinc-400 transition hover:bg-white/5 hover:text-white focus:outline-none focus:ring-2 focus:ring-teal-500"
                >
                    <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                    Back to phases
                </Link>
            </div>

            <header className="border-b border-white/10 pb-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="min-w-0">
                        <p className="text-xs font-semibold uppercase tracking-widest text-zinc-500">
                            {categoryLabels[category] ?? 'Product tasks'}
                        </p>
                        <h1 className="mt-2 break-words text-2xl font-semibold text-white">{phase.name}</h1>
                        <p className="mt-2 max-w-2xl break-words text-sm leading-6 text-zinc-400">
                            {idea.name}
                        </p>
                    </div>
                    <div className="flex-shrink-0 rounded-lg border border-white/10 bg-white/[0.04] px-4 py-3">
                        <p className="text-sm font-semibold text-white">{progress.completed} of {progress.total}</p>
                        <p className="text-xs text-zinc-500">phase tasks complete</p>
                    </div>
                </div>
            </header>

            <section className="mt-6 grid gap-4 lg:grid-cols-2">
                {columns.map((column) => {
                    const tasks = phase.tasks.filter((task) => task.status === column.status);

                    return (
                        <div key={column.status} className="min-w-0 rounded-lg border border-white/10 bg-white/[0.025] p-4">
                            <div className="mb-4 flex items-center justify-between gap-3">
                                <h2 className="text-sm font-semibold uppercase tracking-widest text-zinc-500">
                                    {column.title}
                                </h2>
                                <span className="rounded-full border border-white/10 bg-white/5 px-2 py-0.5 text-xs font-medium text-zinc-400">
                                    {tasks.length}
                                </span>
                            </div>

                            {tasks.length ? (
                                <div className="grid gap-3">
                                    {tasks.map((task) => (
                                        <ActionTaskRow
                                            key={task.id}
                                            ideaId={idea.id}
                                            task={task}
                                            onOpen={setSelectedTask}
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

            {selectedTask ? (
                <ActionTaskModal ideaId={idea.id} task={selectedTask} onClose={() => setSelectedTask(null)} />
            ) : null}
        </AppLayout>
    );
}

import { Link, router } from '@inertiajs/react';
import { CheckCircle2, Circle } from 'lucide-react';

const priorityStyles = {
    High: 'border-red-400/20 bg-red-500/10 text-red-200',
    Medium: 'border-amber-400/20 bg-amber-500/10 text-amber-200',
    Low: 'border-emerald-400/20 bg-emerald-500/10 text-emerald-200',
};

const categoryLabels = {
    product: 'Product',
    marketing: 'Marketing',
    validation: 'Validation',
};

export default function ActionTaskRow({ ideaId, task, compact = false, showCategory = false }) {
    const isCompleted = task.status === 'completed';

    const toggle = (event) => {
        event.stopPropagation();

        router.patch(`/ideas/${ideaId}/tasks/${task.id}`, {
            status: event.target.checked ? 'completed' : 'pending',
        }, {
            preserveScroll: true,
        });
    };

    return (
        <article className={`rounded-lg border border-white/10 bg-zinc-950/55 p-4 shadow-sm shadow-black/20 transition hover:border-teal-500/30 hover:bg-white/[0.045] ${isCompleted ? 'opacity-75' : ''}`}>
            <Link
                href={`/ideas/${ideaId}/tasks/items/${task.id}`}
                className="block w-full rounded-lg text-left focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-zinc-950"
            >
                <div className="mb-3 flex flex-wrap items-center gap-2">
                    <span className="rounded-full border border-white/10 bg-white/5 px-2 py-0.5 text-xs font-medium text-zinc-400">
                        {task.phase}
                    </span>
                    {showCategory ? (
                        <span className="rounded-full border border-sky-400/20 bg-sky-500/10 px-2 py-0.5 text-xs font-medium text-sky-200">
                            {categoryLabels[task.category] ?? 'Product'}
                        </span>
                    ) : null}
                    <span className={`rounded-full border px-2 py-0.5 text-xs font-medium ${priorityStyles[task.priority] ?? priorityStyles.Medium}`}>
                        {task.priority}
                    </span>
                </div>

                <h3 className={`break-words text-sm font-semibold leading-6 ${isCompleted ? 'text-zinc-500 line-through' : 'text-zinc-100'}`}>
                    {task.title}
                </h3>

                {task.description && !compact ? (
                    <p className={`mt-2 break-words text-sm leading-6 ${isCompleted ? 'text-zinc-600 line-through' : 'text-zinc-400'}`}>
                        {task.description}
                    </p>
                ) : null}
            </Link>

            <label className="mt-4 inline-flex cursor-pointer items-center gap-2 rounded-lg text-sm font-medium text-zinc-400 transition hover:text-teal-100 focus-within:outline-none focus-within:ring-2 focus-within:ring-teal-500 focus-within:ring-offset-2 focus-within:ring-offset-zinc-950">
                <input
                    type="checkbox"
                    checked={isCompleted}
                    onChange={toggle}
                    className="sr-only"
                    aria-label={`Mark ${task.title} ${isCompleted ? 'pending' : 'completed'}`}
                />
                {isCompleted ? (
                    <CheckCircle2 className="h-4 w-4 text-emerald-300" aria-hidden="true" />
                ) : (
                    <Circle className="h-4 w-4 text-zinc-500" aria-hidden="true" />
                )}
                {isCompleted ? 'Completed' : 'Mark complete'}
            </label>
        </article>
    );
}

export function taskProgress(tasks) {
    const completed = tasks.filter((task) => task.status === 'completed').length;

    return {
        completed,
        total: tasks.length,
    };
}

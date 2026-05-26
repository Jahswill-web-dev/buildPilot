import { router } from '@inertiajs/react';
import { CheckCircle2, Circle, X } from 'lucide-react';

const categoryLabels = {
    product: 'Product task',
    marketing: 'Marketing task',
    validation: 'Market validation task',
};

export default function ActionTaskModal({ ideaId, task, onClose }) {
    const isCompleted = task.status === 'completed';
    const nextStatus = isCompleted ? 'pending' : 'completed';

    const updateStatus = () => {
        router.patch(`/ideas/${ideaId}/tasks/${task.id}`, {
            status: nextStatus,
        }, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <div className="fixed inset-0 z-[70] flex items-center justify-center bg-black/70 px-4 py-8 backdrop-blur-sm">
            <div className="w-full max-w-xl overflow-hidden rounded-lg border border-white/10 bg-zinc-950 shadow-2xl shadow-black/60">
                <div className="flex items-start justify-between gap-4 border-b border-white/10 p-5">
                    <div className="min-w-0">
                        <p className="text-xs font-semibold uppercase tracking-widest text-zinc-500">
                            {categoryLabels[task.category] ?? 'Product task'}
                        </p>
                        <h2 className="mt-2 break-words text-xl font-semibold leading-7 text-white">{task.title}</h2>
                    </div>
                    <button
                        type="button"
                        title="Close task details"
                        onClick={onClose}
                        className="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-white/5 hover:text-white focus:outline-none focus:ring-2 focus:ring-teal-500"
                    >
                        <X className="h-5 w-5" aria-hidden="true" />
                    </button>
                </div>

                <div className="space-y-5 p-5">
                    <div className="flex flex-wrap gap-2">
                        <span className="rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-xs font-medium text-zinc-300">
                            {task.phase}
                        </span>
                        <span className="rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-xs font-medium text-zinc-300">
                            {task.priority} priority
                        </span>
                        <span className={`rounded-full border px-2.5 py-1 text-xs font-medium ${isCompleted ? 'border-emerald-400/20 bg-emerald-500/10 text-emerald-200' : 'border-amber-400/20 bg-amber-500/10 text-amber-200'}`}>
                            {isCompleted ? 'Completed' : 'Pending'}
                        </span>
                    </div>

                    <div>
                        <h3 className="mb-2 text-xs font-semibold uppercase tracking-widest text-zinc-500">Details</h3>
                        <p className="break-words text-sm leading-6 text-zinc-300">{task.description}</p>
                    </div>

                    <div className="rounded-lg border border-white/10 bg-white/[0.035] p-4">
                        <h3 className="mb-2 text-xs font-semibold uppercase tracking-widest text-zinc-500">Next action</h3>
                        <p className="text-sm leading-6 text-zinc-400">
                            {isCompleted
                                ? 'Reopen this task if it still needs work or new feedback changes the plan.'
                                : 'Complete this task when the outcome is clear enough to move the idea forward.'}
                        </p>
                    </div>
                </div>

                <div className="flex flex-col gap-3 border-t border-white/10 p-5 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-lg px-4 py-2 text-sm font-semibold text-zinc-400 transition hover:bg-white/5 hover:text-white focus:outline-none focus:ring-2 focus:ring-teal-500"
                    >
                        Close
                    </button>
                    <button
                        type="button"
                        onClick={updateStatus}
                        className="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-teal-950/40 transition hover:bg-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-zinc-950"
                    >
                        {isCompleted ? (
                            <Circle className="h-4 w-4" aria-hidden="true" />
                        ) : (
                            <CheckCircle2 className="h-4 w-4" aria-hidden="true" />
                        )}
                        {isCompleted ? 'Mark pending' : 'Mark complete'}
                    </button>
                </div>
            </div>
        </div>
    );
}

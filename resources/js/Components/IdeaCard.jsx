import { useEffect, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { ChevronRight, Trash2, X } from 'lucide-react';
import Button from './Button';

export default function IdeaCard({ idea }) {
    const [confirmingDelete, setConfirmingDelete] = useState(false);
    const [deleting, setDeleting] = useState(false);

    useEffect(() => {
        if (!confirmingDelete) {
            return undefined;
        }

        const closeOnEscape = (event) => {
            if (event.key === 'Escape') {
                setConfirmingDelete(false);
            }
        };

        window.addEventListener('keydown', closeOnEscape);

        return () => window.removeEventListener('keydown', closeOnEscape);
    }, [confirmingDelete]);

    const deleteIdea = () => {
        setDeleting(true);

        router.delete(`/ideas/${idea.id}`, {
            onFinish: () => setDeleting(false),
            onSuccess: () => setConfirmingDelete(false),
        });
    };

    const closeDialog = () => {
        if (!deleting) {
            setConfirmingDelete(false);
        }
    };

    return (
        <article className="group rounded-lg border border-white/10 bg-white/[0.04] px-5 py-4 transition hover:border-teal-500/40 hover:bg-white/[0.07]">
            <div className="flex items-start justify-between gap-4">
                <Link
                    href={`/ideas/${idea.id}`}
                    className="min-w-0 flex-1 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-zinc-950"
                >
                    <div className="flex items-start justify-between gap-3">
                        <div className="min-w-0">
                            <h3 className="break-words text-base font-semibold text-white transition group-hover:text-teal-200">
                                {idea.name}
                            </h3>
                            <p className="mt-1 line-clamp-3 break-words text-sm leading-relaxed text-zinc-400">
                                {idea.description}
                            </p>
                        </div>
                        <span className="mt-1 inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg border border-white/10 bg-white/5 text-zinc-400 transition group-hover:border-teal-500/40 group-hover:text-teal-200">
                            <ChevronRight className="h-4 w-4" aria-hidden="true" />
                        </span>
                    </div>

                    <div className="mt-3 flex flex-wrap items-center gap-3">
                        <span className="text-xs text-zinc-500">{idea.createdRelative}</span>
                        <StatusBadge state={idea.state} />
                    </div>
                </Link>

                <button
                    type="button"
                    title="Delete idea"
                    onClick={() => setConfirmingDelete(true)}
                    className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-zinc-600 transition hover:bg-red-500/10 hover:text-red-300 focus:outline-none focus:ring-2 focus:ring-red-500 sm:opacity-0 sm:group-hover:opacity-100"
                >
                    <Trash2 className="h-4 w-4" aria-hidden="true" />
                </button>
            </div>

            {confirmingDelete ? (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/75 px-4 py-6 backdrop-blur-sm"
                    onMouseDown={closeDialog}
                    role="presentation"
                >
                    <div
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby={`delete-idea-${idea.id}-title`}
                        className="w-full max-w-sm rounded-lg border border-white/10 bg-zinc-950 p-5 shadow-2xl shadow-black/50"
                        onMouseDown={(event) => event.stopPropagation()}
                    >
                        <div className="mb-4 flex items-start justify-between gap-4">
                            <div className="min-w-0">
                                <h2 id={`delete-idea-${idea.id}-title`} className="text-base font-semibold text-white">
                                    Delete idea?
                                </h2>
                                <p className="mt-2 break-words text-sm leading-6 text-zinc-400">
                                    This will permanently delete "{idea.name}".
                                </p>
                            </div>
                            <button
                                type="button"
                                title="Close"
                                onClick={closeDialog}
                                disabled={deleting}
                                className="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-white/5 hover:text-white focus:outline-none focus:ring-2 focus:ring-teal-500 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <X className="h-4 w-4" aria-hidden="true" />
                            </button>
                        </div>

                        <div className="flex flex-wrap justify-end gap-2">
                            <Button type="button" variant="ghost" onClick={closeDialog} disabled={deleting}>
                                Cancel
                            </Button>
                            <Button type="button" variant="danger" processing={deleting} onClick={deleteIdea}>
                                Delete
                            </Button>
                        </div>
                    </div>
                </div>
            ) : null}
        </article>
    );
}

export function StatusBadge({ state }) {
    const styles = {
        done: 'bg-emerald-500/15 text-emerald-300',
        generating: 'bg-teal-500/15 text-teal-200',
        failed: 'bg-red-500/15 text-red-300',
        pending: 'bg-amber-500/15 text-amber-300',
    };
    const label = {
        done: 'Done',
        generating: 'Generating',
        failed: 'Failed',
        pending: 'Pending',
    }[state] ?? state.charAt(0).toUpperCase() + state.slice(1);

    return (
        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${styles[state] ?? styles.pending}`}>
            {label}
        </span>
    );
}

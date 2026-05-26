import { Link, router } from '@inertiajs/react';
import { ChevronRight, Trash2 } from 'lucide-react';

export default function IdeaCard({ idea }) {
    const deleteIdea = () => {
        if (window.confirm('Delete this idea?')) {
            router.delete(`/ideas/${idea.id}`);
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
                    onClick={deleteIdea}
                    className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-zinc-600 transition hover:bg-red-500/10 hover:text-red-300 focus:outline-none focus:ring-2 focus:ring-red-500 sm:opacity-0 sm:group-hover:opacity-100"
                >
                    <Trash2 className="h-4 w-4" aria-hidden="true" />
                </button>
            </div>
        </article>
    );
}

export function StatusBadge({ state }) {
    const isDone = state === 'done';

    return (
        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${isDone ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-500/15 text-amber-300'}`}>
            {state.charAt(0).toUpperCase() + state.slice(1)}
        </span>
    );
}

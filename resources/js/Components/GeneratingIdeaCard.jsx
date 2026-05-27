import { Loader2 } from 'lucide-react';
import GenerationProgressText from './GenerationProgressText';
import { StatusBadge } from './IdeaCard';

export default function GeneratingIdeaCard({ idea }) {
    return (
        <article className="rounded-lg border border-teal-500/20 bg-teal-500/[0.045] px-5 py-4 shadow-lg shadow-teal-950/10">
            <div className="flex items-start justify-between gap-4">
                <div className="min-w-0 flex-1">
                    <div className="mb-3 flex flex-wrap items-center gap-3">
                        <StatusBadge state="generating" />
                        <span className="text-xs text-zinc-500">{idea.createdRelative}</span>
                    </div>

                    <h3 className="break-words text-base font-semibold text-white">{idea.name}</h3>
                    <p className="mt-1 line-clamp-2 break-words text-sm leading-relaxed text-zinc-400">
                        {idea.description}
                    </p>

                    <div className="mt-4">
                        <GenerationProgressText />
                    </div>

                    <div className="mt-4 grid gap-2" aria-hidden="true">
                        <SkeletonLine className="w-11/12" />
                        <SkeletonLine className="w-4/5" />
                        <div className="mt-2 grid grid-cols-3 gap-2">
                            <SkeletonBlock />
                            <SkeletonBlock />
                            <SkeletonBlock />
                        </div>
                    </div>
                </div>

                <span className="mt-1 inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg border border-teal-500/20 bg-teal-500/10 text-teal-200">
                    <Loader2 className="h-4 w-4 animate-spin" aria-hidden="true" />
                </span>
            </div>
        </article>
    );
}

function SkeletonLine({ className = '' }) {
    return (
        <span className={`block h-2 rounded-full bg-white/10 ${className}`}>
            <span className="block h-full animate-pulse rounded-full bg-white/10" />
        </span>
    );
}

function SkeletonBlock() {
    return (
        <span className="block h-10 rounded-lg border border-white/10 bg-white/[0.035]">
            <span className="block h-full animate-pulse rounded-lg bg-white/[0.04]" />
        </span>
    );
}

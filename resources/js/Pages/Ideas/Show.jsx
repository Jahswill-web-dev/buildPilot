import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ChevronRight } from 'lucide-react';
import { taskProgress } from '../../Components/ActionTaskRow';
import AppLayout from '../../Components/AppLayout';
import InlineEditor from '../../Components/InlineEditor';
import { StatusBadge } from '../../Components/IdeaCard';

const GENERATION_FAILURE_MESSAGE = 'This section failed to generate. Try creating the roadmap again.';

const categoryLabels = {
    product: 'Product',
    marketing: 'Marketing',
    validation: 'Validation',
};

export default function Show({ idea }) {
    const progress = taskProgress(idea.actionTasks);
    const previewPhases = attachTaskCountsToPhases(idea.actionPhases ?? [], idea.actionTasks).slice(0, 3);

    const updateIdea = (field, value, callbacks) => {
        router.patch(`/ideas/${idea.id}`, { [field]: value }, {
            preserveScroll: true,
            ...callbacks,
        });
    };

    return (
        <AppLayout maxWidth="max-w-4xl">
            <Head title={idea.name} />

            <div className="mb-6">
                <Link
                    href="/"
                    className="inline-flex items-center gap-2 rounded-lg px-2 py-1 text-sm text-zinc-400 transition hover:bg-white/5 hover:text-white focus:outline-none focus:ring-2 focus:ring-teal-500"
                >
                    <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                    Back
                </Link>
            </div>

            <article className="mt-6 space-y-8">
                <header className="border-b border-white/10 pb-6">
                    <div className="mb-3 flex flex-wrap items-center gap-3">
                        <StatusBadge state={idea.state} />
                        <span className="text-xs text-zinc-500">{idea.createdDate}</span>
                    </div>

                    <InlineEditor
                        label="idea name"
                        value={idea.name}
                        displayClassName="block break-words text-2xl font-semibold text-white transition hover:text-teal-200"
                        inputClassName="text-2xl font-semibold"
                        onSave={(value, callbacks) => updateIdea('name', value, callbacks)}
                    />

                    <div className="mt-4">
                        <InlineEditor
                            label="description"
                            value={idea.description}
                            multiline
                            displayClassName="block whitespace-pre-line break-words text-sm leading-6 text-zinc-300 transition hover:text-zinc-100"
                            onSave={(value, callbacks) => updateIdea('description', value, callbacks)}
                        />
                    </div>
                </header>

                {idea.targetUser ? (
                    <section className="rounded-lg border border-white/10 bg-white/[0.035] p-5">
                        <h2 className="mb-4 text-sm font-semibold uppercase tracking-widest text-zinc-500">
                            Target User
                        </h2>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <TargetUserField label="User type" value={idea.targetUser.user_type} />
                            <TargetUserField label="Main problem" value={idea.targetUser.main_problem} />
                            <TargetUserField label="Current workaround" value={idea.targetUser.current_workaround} />
                            <TargetUserField label="Why they care" value={idea.targetUser.why_they_care} />
                        </div>
                    </section>
                ) : null}

                {idea.problemStatement ? (
                    <section className="rounded-lg border border-white/10 bg-white/[0.035] p-5">
                        <h2 className="mb-3 text-sm font-semibold uppercase tracking-widest text-zinc-500">
                            Problem Statement
                        </h2>
                        <p className={`break-words text-sm leading-6 ${isGenerationFailure(idea.problemStatement) ? 'text-zinc-500' : 'text-zinc-200'}`}>
                            {idea.problemStatement}
                        </p>
                    </section>
                ) : null}

                {idea.desiredOutcome ? (
                    <section className="rounded-lg border border-white/10 bg-white/[0.035] p-5">
                        <h2 className="mb-3 text-sm font-semibold uppercase tracking-widest text-zinc-500">
                            Desired Outcome
                        </h2>
                        <p className={`break-words text-sm leading-6 ${isGenerationFailure(idea.desiredOutcome) ? 'text-zinc-500' : 'text-zinc-200'}`}>
                            {idea.desiredOutcome}
                        </p>
                    </section>
                ) : null}

                {idea.coreFeatures ? (
                    <section className="rounded-lg border border-white/10 bg-white/[0.035] p-5">
                        <h2 className="mb-4 text-sm font-semibold uppercase tracking-widest text-zinc-500">
                            Core Features + User Flow
                        </h2>

                        <div className="divide-y divide-white/10">
                            {idea.coreFeatures.map((feature, index) => (
                                <CoreFeatureItem key={`${feature.feature}-${index}`} feature={feature} index={index} />
                            ))}
                        </div>
                    </section>
                ) : null}

                {idea.mvpScope ? (
                    <section className="rounded-lg border border-white/10 bg-white/[0.035] p-5">
                        <h2 className="mb-4 text-sm font-semibold uppercase tracking-widest text-zinc-500">
                            MVP Scope
                        </h2>

                        <div className="grid gap-5 md:grid-cols-3">
                            <ScopeGroup title="Must-have" items={idea.mvpScope.must_have} />
                            <ScopeGroup title="Nice-to-have" items={idea.mvpScope.nice_to_have} />
                            <ScopeGroup title="Later" items={idea.mvpScope.later} />
                        </div>
                    </section>
                ) : null}

                <section className="rounded-lg border border-white/10 bg-white/[0.035] p-5">
                    <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="text-sm font-semibold uppercase tracking-widest text-zinc-500">
                                Action Plan
                            </h2>
                            <p className="mt-1 text-sm text-zinc-400">
                                {progress.completed} of {progress.total} complete
                            </p>
                        </div>

                        <Link
                            href={`/ideas/${idea.id}/tasks`}
                            className="inline-flex items-center gap-2 self-start rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm font-semibold text-zinc-200 transition hover:border-teal-500/40 hover:text-teal-100 focus:outline-none focus:ring-2 focus:ring-teal-500 sm:self-auto"
                        >
                            See all tasks
                            <ChevronRight className="h-4 w-4" aria-hidden="true" />
                        </Link>
                    </div>

                    <div className="grid gap-3 md:grid-cols-3">
                        {previewPhases.map((phase) => (
                            <Link
                                key={phase.slug}
                                href={`/ideas/${idea.id}/tasks/phases/${phase.slug}`}
                                className="group min-w-0 rounded-lg border border-white/10 bg-zinc-950/55 p-4 transition hover:border-teal-500/30 hover:bg-white/[0.045] focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-zinc-950"
                            >
                                <div className="mb-3 flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="text-xs font-semibold uppercase tracking-widest text-zinc-500">Phase</p>
                                        <h3 className="mt-2 break-words text-sm font-semibold leading-6 text-zinc-100 transition group-hover:text-teal-100">
                                            {phase.title}
                                        </h3>
                                    </div>
                                    <ChevronRight className="h-4 w-4 flex-shrink-0 text-zinc-500 transition group-hover:text-teal-200" aria-hidden="true" />
                                </div>

                                {phase.description ? (
                                    <p className="line-clamp-3 break-words text-sm leading-6 text-zinc-400">
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

                                <div className="mt-4 grid grid-cols-3 gap-2">
                                    <PhaseStat label="Tasks" value={phase.total} />
                                    <PhaseStat label="Done" value={phase.completed} />
                                    <PhaseStat label="Open" value={phase.pending} />
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>
            </article>
        </AppLayout>
    );
}

function TargetUserField({ label, value }) {
    return (
        <div className="min-w-0">
            <h3 className="mb-1 text-xs font-semibold uppercase text-zinc-500">{label}</h3>
            <p className={`break-words text-sm leading-6 ${isGenerationFailure(value) ? 'text-zinc-500' : 'text-zinc-200'}`}>
                {value}
            </p>
        </div>
    );
}

function CoreFeatureItem({ feature, index }) {
    const isFailed = !feature.reason;

    return (
        <div className="grid gap-3 py-4 first:pt-0 last:pb-0 sm:grid-cols-[2rem_1fr]">
            <span className="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 text-xs font-semibold text-zinc-500">
                {index + 1}
            </span>
            <div className="min-w-0">
                <h3 className={`break-words text-sm font-semibold ${isFailed ? 'text-zinc-400' : 'text-zinc-100'}`}>
                    {feature.feature}
                </h3>
                {feature.reason ? (
                    <p className="mt-1 break-words text-sm leading-6 text-zinc-400">{feature.reason}</p>
                ) : null}
            </div>
        </div>
    );
}

function ScopeGroup({ title, items = [] }) {
    return (
        <div className="min-w-0">
            <h3 className="mb-3 text-xs font-semibold uppercase text-zinc-500">{title}</h3>
            <ul className="space-y-2">
                {items.map((item, index) => (
                    <li
                        key={`${title}-${item}-${index}`}
                        className={`break-words text-sm leading-6 ${isGenerationFailure(item) ? 'text-zinc-500' : 'text-zinc-300'}`}
                    >
                        {item}
                    </li>
                ))}
            </ul>
        </div>
    );
}

function PhaseStat({ label, value }) {
    return (
        <div className="rounded-lg border border-white/10 bg-white/[0.035] px-2 py-2">
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

function isGenerationFailure(value) {
    return value === GENERATION_FAILURE_MESSAGE;
}

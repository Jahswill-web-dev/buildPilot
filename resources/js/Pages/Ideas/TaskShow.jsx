import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Circle } from 'lucide-react';
import AppLayout from '../../Components/AppLayout';
import Button from '../../Components/Button';

const categoryLabels = {
    product: 'Product task',
    marketing: 'Marketing task',
    validation: 'Market validation task',
};

export default function TaskShow({ idea, task, phase }) {
    const isCompleted = task.status === 'completed';
    const nextStatus = isCompleted ? 'pending' : 'completed';
    const backHref = phase?.slug ? `/ideas/${idea.id}/tasks/phases/${phase.slug}` : `/ideas/${idea.id}/tasks`;

    const updateStatus = () => {
        router.patch(`/ideas/${idea.id}/tasks/${task.id}`, {
            status: nextStatus,
        }, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout maxWidth="max-w-4xl">
            <Head title={`${task.title} - ${idea.name}`} />

            <div className="mb-6">
                <Link
                    href={backHref}
                    className="inline-flex items-center gap-2 rounded-lg px-2 py-1 text-sm text-zinc-400 transition hover:bg-white/5 hover:text-white focus:outline-none focus:ring-2 focus:ring-teal-500"
                >
                    <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                    Back to phase
                </Link>
            </div>

            <article className="space-y-6">
                <header className="border-b border-white/10 pb-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div className="min-w-0">
                            <p className="text-xs font-semibold uppercase tracking-widest text-zinc-500">
                                {categoryLabels[task.category] ?? 'Product task'}
                            </p>
                            <h1 className="mt-2 break-words text-2xl font-semibold leading-8 text-white">
                                {task.title}
                            </h1>
                            <p className="mt-3 break-words text-sm leading-6 text-zinc-400">
                                {task.description}
                            </p>
                        </div>

                        <Button
                            type="button"
                            onClick={updateStatus}
                            className="inline-flex flex-shrink-0 items-center justify-center gap-2 self-start"
                            variant={isCompleted ? 'secondary' : 'primary'}
                        >
                            {isCompleted ? (
                                <Circle className="h-4 w-4" aria-hidden="true" />
                            ) : (
                                <CheckCircle2 className="h-4 w-4" aria-hidden="true" />
                            )}
                            {isCompleted ? 'Mark pending' : 'Mark complete'}
                        </Button>
                    </div>

                    <div className="mt-5 flex flex-wrap gap-2">
                        <Badge>{phase?.title ?? task.phase}</Badge>
                        {task.taskType && task.taskType !== 'other' ? <Badge tone="sky">{formatLabel(task.taskType)}</Badge> : null}
                        <Badge>{task.priority} priority</Badge>
                        {task.estimatedTimeMinutes ? <Badge>{task.estimatedTimeMinutes} min</Badge> : null}
                        <Badge tone={isCompleted ? 'green' : 'amber'}>{isCompleted ? 'Completed' : 'Pending'}</Badge>
                    </div>
                </header>

                <section className="grid gap-4 md:grid-cols-2">
                    <InfoBlock title="Deliverable" text={task.deliverable} />
                    <InfoBlock title="Definition of done" text={task.definitionOfDone} />
                    <InfoBlock title="Why it matters" text={task.whyItMatters} wide />
                </section>

                <DetailList title="Steps" items={task.steps} ordered />
                <DetailList title="Interview questions" items={task.interviewQuestions} />
                <DetailList title="Research checklist" items={task.researchChecklist} />
                <DetailList title="Copy examples" items={task.copyExamples} />
                <InfoBlock title="Outreach message" text={task.outreachMessage} preserveLines />
                <DetailList title="Implementation notes" items={task.implementationNotes} />
                <DetailList title="Acceptance criteria" items={task.acceptanceCriteria} />
                <DetailList title="Metrics to track" items={task.metricsToTrack} />
            </article>
        </AppLayout>
    );
}

function Badge({ children, tone = 'default' }) {
    const styles = {
        default: 'border-white/10 bg-white/5 text-zinc-300',
        sky: 'border-sky-400/20 bg-sky-500/10 text-sky-200',
        green: 'border-emerald-400/20 bg-emerald-500/10 text-emerald-200',
        amber: 'border-amber-400/20 bg-amber-500/10 text-amber-200',
    };

    return (
        <span className={`rounded-full border px-2.5 py-1 text-xs font-medium ${styles[tone] ?? styles.default}`}>
            {children}
        </span>
    );
}

function InfoBlock({ title, text, preserveLines = false, wide = false }) {
    if (!text) {
        return null;
    }

    return (
        <div className={`min-w-0 rounded-lg border border-white/10 bg-white/[0.025] p-4 ${wide ? 'md:col-span-2' : ''}`}>
            <h2 className="text-xs font-semibold uppercase tracking-widest text-zinc-500">{title}</h2>
            <p className={`mt-2 break-words text-sm leading-6 text-zinc-300 ${preserveLines ? 'whitespace-pre-line' : ''}`}>
                {text}
            </p>
        </div>
    );
}

function DetailList({ title, items = [], ordered = false }) {
    if (!items.length) {
        return null;
    }

    const List = ordered ? 'ol' : 'ul';

    return (
        <section className="rounded-lg border border-white/10 bg-white/[0.025] p-4">
            <h2 className="text-xs font-semibold uppercase tracking-widest text-zinc-500">{title}</h2>
            <List className={`mt-3 space-y-2 text-sm leading-6 text-zinc-300 ${ordered ? 'list-decimal pl-5' : 'list-disc pl-5'}`}>
                {items.map((item, index) => (
                    <li key={`${title}-${index}`} className="break-words">
                        {item}
                    </li>
                ))}
            </List>
        </section>
    );
}

function formatLabel(value) {
    return value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

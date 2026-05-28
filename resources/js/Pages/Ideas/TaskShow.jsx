import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Circle } from 'lucide-react';
import { useEffect, useState } from 'react';
import AppLayout from '../../Components/AppLayout';
import Button from '../../Components/Button';
import InlineEditor from '../../Components/InlineEditor';

const categoryLabels = {
    product: 'Product task',
    marketing: 'Marketing task',
    validation: 'Market validation task',
};

export default function TaskShow({ idea, task, phase }) {
    const [editableTask, setEditableTask] = useState(task);
    const isCompleted = editableTask.status === 'completed';
    const nextStatus = isCompleted ? 'pending' : 'completed';
    const backHref = phase?.slug ? `/ideas/${idea.id}/tasks/phases/${phase.slug}` : `/ideas/${idea.id}/tasks`;

    useEffect(() => {
        setEditableTask(task);
    }, [task]);

    const updateStatus = () => {
        router.patch(`/ideas/${idea.id}/tasks/${task.id}`, {
            status: nextStatus,
        }, {
            preserveScroll: true,
            onSuccess: () => setEditableTask((currentTask) => ({
                ...currentTask,
                status: nextStatus,
            })),
        });
    };

    const updateTask = (field, value, callbacks) => {
        const nextTask = {
            ...editableTask,
            [field]: value,
        };

        setEditableTask(nextTask);

        router.patch(`/ideas/${idea.id}/tasks/${task.id}`, {
            [field]: value,
        }, {
            preserveScroll: true,
            ...callbacks,
        });
    };

    const updateTaskListItem = (field, index, value, callbacks) => {
        const nextItems = editableTask[field].map((item, itemIndex) => (
            itemIndex === index ? value : item
        ));

        updateTask(field, nextItems, callbacks);
    };

    return (
        <AppLayout maxWidth="max-w-4xl">
            <Head title={`${editableTask.title} - ${idea.name}`} />

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
                                {categoryLabels[editableTask.category] ?? 'Product task'}
                            </p>
                            <div className="mt-2">
                                <InlineEditor
                                    label="task title"
                                    value={editableTask.title}
                                    displayClassName="block break-words text-2xl font-semibold leading-8 text-white transition hover:text-teal-100"
                                    inputClassName="font-semibold leading-8"
                                    onSave={(value, callbacks) => updateTask('title', value, callbacks)}
                                />
                            </div>
                            <div className="mt-3">
                                <InlineEditor
                                    label="task description"
                                    value={editableTask.description}
                                    multiline
                                    displayClassName="block whitespace-pre-line break-words text-sm leading-6 text-zinc-400 transition hover:text-zinc-200"
                                    onSave={(value, callbacks) => updateTask('description', value, callbacks)}
                                />
                            </div>
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
                        <Badge>{phase?.title ?? editableTask.phase}</Badge>
                        {editableTask.taskType && editableTask.taskType !== 'other' ? <Badge tone="sky">{formatLabel(editableTask.taskType)}</Badge> : null}
                        <Badge>{editableTask.priority} priority</Badge>
                        {editableTask.estimatedTimeMinutes ? <Badge>{editableTask.estimatedTimeMinutes} min</Badge> : null}
                        <Badge tone={isCompleted ? 'green' : 'amber'}>{isCompleted ? 'Completed' : 'Pending'}</Badge>
                    </div>
                </header>

                <section className="grid gap-4 md:grid-cols-2">
                    <InfoBlock
                        title="Deliverable"
                        text={editableTask.deliverable}
                        onSave={(value, callbacks) => updateTask('deliverable', value, callbacks)}
                    />
                    <InfoBlock
                        title="Definition of done"
                        text={editableTask.definitionOfDone}
                        onSave={(value, callbacks) => updateTask('definitionOfDone', value, callbacks)}
                    />
                    <InfoBlock
                        title="Why it matters"
                        text={editableTask.whyItMatters}
                        wide
                        onSave={(value, callbacks) => updateTask('whyItMatters', value, callbacks)}
                    />
                </section>

                <DetailList
                    title="Steps"
                    items={editableTask.steps}
                    ordered
                    onSave={(index, value, callbacks) => updateTaskListItem('steps', index, value, callbacks)}
                />
                <DetailList
                    title="Interview questions"
                    items={editableTask.interviewQuestions}
                    onSave={(index, value, callbacks) => updateTaskListItem('interviewQuestions', index, value, callbacks)}
                />
                <DetailList
                    title="Research checklist"
                    items={editableTask.researchChecklist}
                    onSave={(index, value, callbacks) => updateTaskListItem('researchChecklist', index, value, callbacks)}
                />
                <DetailList
                    title="Copy examples"
                    items={editableTask.copyExamples}
                    onSave={(index, value, callbacks) => updateTaskListItem('copyExamples', index, value, callbacks)}
                />
                <InfoBlock
                    title="Outreach message"
                    text={editableTask.outreachMessage}
                    preserveLines
                    onSave={(value, callbacks) => updateTask('outreachMessage', value, callbacks)}
                />
                <DetailList
                    title="Implementation notes"
                    items={editableTask.implementationNotes}
                    onSave={(index, value, callbacks) => updateTaskListItem('implementationNotes', index, value, callbacks)}
                />
                <DetailList
                    title="Acceptance criteria"
                    items={editableTask.acceptanceCriteria}
                    onSave={(index, value, callbacks) => updateTaskListItem('acceptanceCriteria', index, value, callbacks)}
                />
                <DetailList
                    title="Metrics to track"
                    items={editableTask.metricsToTrack}
                    onSave={(index, value, callbacks) => updateTaskListItem('metricsToTrack', index, value, callbacks)}
                />
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

function InfoBlock({ title, text, preserveLines = false, wide = false, onSave }) {
    if (!text) {
        return null;
    }

    return (
        <div className={`min-w-0 rounded-lg border border-white/10 bg-white/[0.025] p-4 ${wide ? 'md:col-span-2' : ''}`}>
            <h2 className="text-xs font-semibold uppercase tracking-widest text-zinc-500">{title}</h2>
            <div className="mt-2">
                <InlineEditor
                    label={title.toLowerCase()}
                    value={text}
                    multiline={preserveLines}
                    displayClassName={`block break-words text-sm leading-6 text-zinc-300 transition hover:text-zinc-100 ${preserveLines ? 'whitespace-pre-line' : ''}`}
                    onSave={onSave}
                />
            </div>
        </div>
    );
}

function DetailList({ title, items = [], ordered = false, onSave }) {
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
                        <InlineEditor
                            label={`${title.toLowerCase()} item`}
                            value={item}
                            multiline
                            displayClassName="block whitespace-pre-line break-words text-sm leading-6 text-zinc-300 transition hover:text-zinc-100"
                            onSave={(value, callbacks) => onSave(index, value, callbacks)}
                        />
                    </li>
                ))}
            </List>
        </section>
    );
}

function formatLabel(value) {
    return value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

import {
    DndContext,
    KeyboardSensor,
    PointerSensor,
    closestCenter,
    useDraggable,
    useDroppable,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Sparkles } from 'lucide-react';
import { useEffect, useState } from 'react';
import ActionTaskRow, { taskProgress } from '../../Components/ActionTaskRow';
import AppLayout from '../../Components/AppLayout';
import Button from '../../Components/Button';
import ErrorSummary from '../../Components/ErrorSummary';

const columns = [
    { title: 'Pending', status: 'pending' },
    { title: 'Completed', status: 'completed' },
];

const categoryLabels = {
    product: 'Product tasks',
    marketing: 'Marketing tasks',
    validation: 'Market validation',
};

const shortCategoryLabels = {
    product: 'Product',
    marketing: 'Marketing',
    validation: 'Validation',
};

export default function TaskPhase({ idea, category, phase }) {
    const generateForm = useForm({});
    const [tasks, setTasks] = useState(phase.tasks);
    const [taskError, setTaskError] = useState(null);
    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: {
                distance: 8,
            },
        }),
        useSensor(KeyboardSensor),
    );
    const progress = taskProgress(tasks);
    const isGlobalPhase = !category;

    useEffect(() => {
        setTasks(phase.tasks);
    }, [phase.tasks]);

    const generateTasks = () => {
        generateForm.post(`/ideas/${idea.id}/tasks/phases/${phase.slug}/generate`, {
            preserveScroll: true,
        });
    };

    const updateTaskStatus = (taskId, status) => {
        const previousTasks = tasks;
        const task = previousTasks.find((item) => item.id === taskId);

        if (!task || task.status === status) {
            return;
        }

        setTaskError(null);
        setTasks(previousTasks.map((item) => (
            item.id === taskId ? { ...item, status } : item
        )));

        router.patch(`/ideas/${idea.id}/tasks/${taskId}`, {
            status,
        }, {
            preserveScroll: true,
            onError: () => {
                setTasks(previousTasks);
                setTaskError('Task status could not be saved. Try again.');
            },
        });
    };

    const handleDragEnd = (event) => {
        const taskId = event.active?.id;
        const status = event.over?.id;

        if (!taskId || !['pending', 'completed'].includes(status)) {
            return;
        }

        updateTaskStatus(taskId, status);
    };

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

            <ErrorSummary errors={generateForm.errors} />
            <ErrorSummary errors={taskError ? { taskStatus: taskError } : {}} />

            <header className="border-b border-white/10 pb-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="min-w-0">
                        <p className="text-xs font-semibold uppercase tracking-widest text-zinc-500">
                            {isGlobalPhase ? 'Phase tasks' : categoryLabels[category] ?? 'Product tasks'}
                        </p>
                        <h1 className="mt-2 break-words text-2xl font-semibold text-white">{phase.name}</h1>
                        <p className="mt-2 max-w-2xl break-words text-sm leading-6 text-zinc-400">
                            {isGlobalPhase ? `${idea.name} - product, marketing, and validation tasks` : idea.name}
                        </p>
                    </div>
                    <div className="flex flex-shrink-0 flex-col gap-3 sm:items-end">
                        {isGlobalPhase ? (
                            <Button
                                type="button"
                                onClick={generateTasks}
                                processing={generateForm.processing}
                                className="inline-flex items-center justify-center gap-2"
                            >
                                <Sparkles className="h-4 w-4" aria-hidden="true" />
                                Generate tasks
                            </Button>
                        ) : null}
                        <div className="rounded-lg border border-white/10 bg-white/[0.04] px-4 py-3">
                            <p className="text-sm font-semibold text-white">{progress.completed} of {progress.total}</p>
                            <p className="text-xs text-zinc-500">phase tasks complete</p>
                        </div>
                    </div>
                </div>
            </header>

            {(phase.description || phase.goal || phase.successCriteria) ? (
                <section className="mt-6 grid gap-4 lg:grid-cols-3">
                    {phase.description ? (
                        <PhaseInfo title="Description" text={phase.description} />
                    ) : null}
                    {phase.goal ? (
                        <PhaseInfo title="Goal" text={phase.goal} />
                    ) : null}
                    {phase.successCriteria ? (
                        <PhaseInfo title="Success criteria" text={phase.successCriteria} />
                    ) : null}
                </section>
            ) : null}

            {phase.includedCategories?.length ? (
                <div className="mt-5 flex flex-wrap gap-2">
                    {phase.includedCategories.map((category) => (
                        <span
                            key={category}
                            className="rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-xs font-medium text-zinc-300"
                        >
                            {shortCategoryLabels[category] ?? 'Product'}
                        </span>
                    ))}
                </div>
            ) : null}

            <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                <section className="mt-6 grid gap-4 lg:grid-cols-2">
                    {columns.map((column) => (
                        <TaskColumn
                            key={column.status}
                            column={column}
                            tasks={tasks.filter((task) => task.status === column.status)}
                            ideaId={idea.id}
                            showCategory={isGlobalPhase}
                            onStatusChange={updateTaskStatus}
                        />
                    ))}
                </section>
            </DndContext>
        </AppLayout>
    );
}

function TaskColumn({ column, tasks, ideaId, showCategory, onStatusChange }) {
    const { isOver, setNodeRef } = useDroppable({
        id: column.status,
    });

    return (
        <div
            ref={setNodeRef}
            className={`min-w-0 rounded-lg border p-4 transition ${isOver ? 'border-teal-400/60 bg-teal-500/[0.08]' : 'border-white/10 bg-white/[0.025]'}`}
        >
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
                        <DraggableTask
                            key={task.id}
                            ideaId={ideaId}
                            task={task}
                            showCategory={showCategory}
                            onStatusChange={onStatusChange}
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
}

function DraggableTask({ ideaId, task, showCategory, onStatusChange }) {
    const { attributes, listeners, setActivatorNodeRef, setNodeRef, transform, isDragging } = useDraggable({
        id: task.id,
    });
    const style = transform ? {
        transform: `translate3d(${transform.x}px, ${transform.y}px, 0)`,
        zIndex: 40,
        position: 'relative',
    } : undefined;

    return (
        <div ref={setNodeRef}>
            <ActionTaskRow
                ideaId={ideaId}
                task={task}
                showCategory={showCategory}
                onStatusChange={onStatusChange}
                draggableAttributes={attributes}
                draggableListeners={listeners}
                dragHandleRef={setActivatorNodeRef}
                isDragging={isDragging}
                style={style}
            />
        </div>
    );
}

function PhaseInfo({ title, text }) {
    return (
        <div className="min-w-0 rounded-lg border border-white/10 bg-white/[0.025] p-4">
            <h2 className="text-xs font-semibold uppercase tracking-widest text-zinc-500">{title}</h2>
            <p className="mt-2 break-words text-sm leading-6 text-zinc-300">{text}</p>
        </div>
    );
}

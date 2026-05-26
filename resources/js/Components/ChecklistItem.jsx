import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Edit3, Trash2 } from 'lucide-react';
import Button from './Button';
import TextInput from './TextInput';

export default function ChecklistItem({ ideaId, item }) {
    const [editing, setEditing] = useState(false);
    const form = useForm({ text: item.title });

    const toggle = (event) => {
        router.patch(`/ideas/${ideaId}/checklist-items/${item.id}`, {
            done: event.target.checked,
        }, {
            preserveScroll: true,
        });
    };

    const submit = (event) => {
        event.preventDefault();
        form.patch(`/ideas/${ideaId}/checklist-items/${item.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditing(false),
        });
    };

    const destroy = () => {
        if (window.confirm('Delete this checklist item?')) {
            router.delete(`/ideas/${ideaId}/checklist-items/${item.id}`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <div className="rounded-lg border border-white/10 bg-white/[0.04] px-4 py-3">
            <div className="flex items-start gap-3">
                <input
                    type="checkbox"
                    checked={item.done}
                    onChange={toggle}
                    className="mt-1 h-5 w-5 cursor-pointer rounded border-white/20 bg-white/5 text-teal-600 focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-zinc-950"
                    aria-label="Toggle checklist item"
                />

                <div className="min-w-0 flex-1">
                    {editing ? (
                        <form onSubmit={submit} className="space-y-3">
                            <TextInput
                                value={form.data.text}
                                onChange={(event) => form.setData('text', event.target.value)}
                                disabled={form.processing}
                                autoFocus
                            />
                            {form.errors.text ? <p className="text-sm text-red-300">{form.errors.text}</p> : null}
                            <div className="flex flex-wrap gap-2">
                                <Button type="submit" className="px-3 py-1.5" processing={form.processing}>Save</Button>
                                <Button type="button" variant="ghost" className="px-3 py-1.5" onClick={() => setEditing(false)}>Cancel</Button>
                            </div>
                        </form>
                    ) : (
                        <button
                            type="button"
                            onClick={() => setEditing(true)}
                            className="block w-full rounded-lg text-left focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-zinc-950"
                        >
                            <span className={`block break-words text-sm leading-6 transition hover:text-teal-100 ${item.done ? 'text-zinc-500 line-through' : 'text-zinc-200'}`}>
                                {item.title}
                            </span>
                            {item.description ? (
                                <span className={`mt-1 block break-words text-xs leading-5 ${item.done ? 'text-zinc-600 line-through' : 'text-zinc-400'}`}>
                                    {item.description}
                                </span>
                            ) : null}
                        </button>
                    )}
                </div>

                <button
                    type="button"
                    title="Edit checklist item"
                    onClick={() => setEditing(true)}
                    className="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-white/5 hover:text-teal-200 focus:outline-none focus:ring-2 focus:ring-teal-500"
                >
                    <Edit3 className="h-4 w-4" aria-hidden="true" />
                </button>
                <button
                    type="button"
                    title="Delete checklist item"
                    onClick={destroy}
                    className="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-zinc-600 transition hover:bg-red-500/10 hover:text-red-300 focus:outline-none focus:ring-2 focus:ring-red-500"
                >
                    <Trash2 className="h-4 w-4" aria-hidden="true" />
                </button>
            </div>
        </div>
    );
}

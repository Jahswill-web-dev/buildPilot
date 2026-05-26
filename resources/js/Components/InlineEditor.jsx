import { useState } from 'react';
import { Edit3 } from 'lucide-react';
import Button from './Button';
import TextInput from './TextInput';

export default function InlineEditor({ value, multiline = false, label, displayClassName = '', inputClassName = '', onSave }) {
    const [editing, setEditing] = useState(false);
    const [draft, setDraft] = useState(value);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState(null);

    const cancel = () => {
        setDraft(value);
        setError(null);
        setEditing(false);
    };

    const submit = (event) => {
        event.preventDefault();
        setProcessing(true);
        setError(null);

        onSave(draft, {
            onError: (errors) => setError(Object.values(errors)[0] || null),
            onFinish: () => setProcessing(false),
            onSuccess: () => setEditing(false),
        });
    };

    if (editing) {
        return (
            <form onSubmit={submit} className="space-y-3">
                <TextInput
                    multiline={multiline}
                    rows={multiline ? 6 : undefined}
                    value={draft}
                    onChange={(event) => setDraft(event.target.value)}
                    className={inputClassName}
                    disabled={processing}
                    autoFocus
                />
                {error ? <p className="text-sm text-red-300">{error}</p> : null}
                <div className="flex flex-wrap gap-2">
                    <Button type="submit" processing={processing}>Save</Button>
                    <Button type="button" variant="ghost" onClick={cancel}>Cancel</Button>
                </div>
            </form>
        );
    }

    return (
        <div className="group flex items-start gap-3">
            <button
                type="button"
                onClick={() => setEditing(true)}
                className="min-w-0 flex-1 rounded-lg text-left focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-zinc-950"
            >
                <span className={displayClassName}>{value}</span>
            </button>
            <button
                type="button"
                title={`Edit ${label}`}
                onClick={() => setEditing(true)}
                className="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg border border-white/10 bg-white/5 text-zinc-400 transition hover:border-teal-500/40 hover:text-teal-200 focus:outline-none focus:ring-2 focus:ring-teal-500"
            >
                <Edit3 className="h-4 w-4" aria-hidden="true" />
            </button>
        </div>
    );
}

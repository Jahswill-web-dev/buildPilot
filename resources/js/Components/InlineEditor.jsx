import { useEffect, useRef, useState } from 'react';

const AUTOSAVE_DELAY = 700;

export default function InlineEditor({
    value,
    multiline = false,
    label,
    displayClassName = '',
    inputClassName = '',
    emptyText = '',
    onSave,
}) {
    const [draft, setDraft] = useState(value ?? '');
    const [lastSavedValue, setLastSavedValue] = useState(value ?? '');
    const [saveState, setSaveState] = useState('idle');
    const [error, setError] = useState(null);
    const fieldRef = useRef(null);
    const timerRef = useRef(null);
    const statusTimerRef = useRef(null);
    const savingRef = useRef(false);
    const mountedRef = useRef(true);
    const pendingValueRef = useRef(null);
    const draftRef = useRef(draft);
    const lastSavedRef = useRef(lastSavedValue);

    useEffect(() => {
        draftRef.current = draft;
    }, [draft]);

    useEffect(() => {
        lastSavedRef.current = lastSavedValue;
    }, [lastSavedValue]);

    useEffect(() => {
        const nextValue = value ?? '';
        const isDirty = draftRef.current !== lastSavedRef.current;

        setLastSavedValue(nextValue);

        if (!isDirty || nextValue === draftRef.current) {
            setDraft(nextValue);
            setSaveState('idle');
            setError(null);
        }
    }, [value]);

    useEffect(() => {
        if (fieldRef.current) {
            fieldRef.current.style.height = 'auto';
            fieldRef.current.style.height = `${fieldRef.current.scrollHeight}px`;
        }
    }, [draft]);

    useEffect(() => () => {
        mountedRef.current = false;
        clearAutosaveTimer();
        clearStatusTimer();

        if (draftRef.current !== lastSavedRef.current) {
            save(draftRef.current);
        }
    }, []);

    const clearAutosaveTimer = () => {
        if (timerRef.current) {
            window.clearTimeout(timerRef.current);
            timerRef.current = null;
        }
    };

    const clearStatusTimer = () => {
        if (statusTimerRef.current) {
            window.clearTimeout(statusTimerRef.current);
            statusTimerRef.current = null;
        }
    };

    const save = (nextValue) => {
        if (nextValue === lastSavedRef.current) {
            if (mountedRef.current) {
                setSaveState('idle');
                setError(null);
            }

            return;
        }

        if (savingRef.current) {
            pendingValueRef.current = nextValue;
            return;
        }

        savingRef.current = true;
        pendingValueRef.current = null;
        clearStatusTimer();

        if (mountedRef.current) {
            setSaveState('saving');
            setError(null);
        }

        onSave(nextValue, {
            onError: (errors) => {
                if (mountedRef.current) {
                    setError(Object.values(errors)[0] || 'Unable to save changes.');
                    setSaveState('error');
                }
            },
            onFinish: () => {
                savingRef.current = false;

                if (pendingValueRef.current !== null && pendingValueRef.current !== nextValue) {
                    const pendingValue = pendingValueRef.current;
                    pendingValueRef.current = null;
                    save(pendingValue);
                }
            },
            onSuccess: () => {
                lastSavedRef.current = nextValue;

                if (mountedRef.current) {
                    setLastSavedValue(nextValue);
                    setSaveState(nextValue === draftRef.current ? 'saved' : 'saving');
                    setError(null);
                }

                clearStatusTimer();
                statusTimerRef.current = window.setTimeout(() => {
                    if (mountedRef.current && lastSavedRef.current === draftRef.current) {
                        setSaveState('idle');
                    }
                }, 1200);
            },
        });
    };

    const scheduleSave = (nextValue) => {
        clearAutosaveTimer();

        if (nextValue === lastSavedRef.current) {
            setSaveState('idle');
            setError(null);
            return;
        }

        setSaveState('pending');
        timerRef.current = window.setTimeout(() => save(nextValue), AUTOSAVE_DELAY);
    };

    const updateDraft = (event) => {
        const nextValue = event.target.value;

        setDraft(nextValue);
        scheduleSave(nextValue);
    };

    const flushSave = () => {
        clearAutosaveTimer();
        save(draftRef.current);
    };

    const statusText = error || {
        saving: 'Saving...',
        saved: 'Saved',
    }[saveState];
    const statusClassName = error ? 'text-red-300' : 'text-zinc-600';
    const editableClassName = `w-full cursor-text border-0 bg-transparent p-0 text-left outline-none transition placeholder-zinc-600 hover:decoration-dotted hover:underline focus:decoration-teal-400/70 focus:underline ${displayClassName} ${inputClassName}`;

    return (
        <div className="min-w-0 w-full">
            <textarea
                ref={fieldRef}
                aria-label={`Edit ${label}`}
                value={draft}
                placeholder={emptyText}
                rows={1}
                onChange={updateDraft}
                onBlur={flushSave}
                className={`${editableClassName} block min-h-[1lh] resize-none overflow-hidden whitespace-pre-wrap`}
            />
            {statusText ? (
                <p className={`mt-1 text-[11px] leading-4 ${statusClassName}`}>{statusText}</p>
            ) : null}
        </div>
    );
}

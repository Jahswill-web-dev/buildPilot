import { router } from '@inertiajs/react';
import { useEffect } from 'react';

export default function useGeneratingIdeasPoll(ideas, intervalMs = 3500) {
    const hasGeneratingIdeas = ideas.some((idea) => idea.state === 'generating');

    useEffect(() => {
        if (!hasGeneratingIdeas) {
            return undefined;
        }

        const interval = window.setInterval(() => {
            router.reload({
                only: ['ideas'],
                preserveScroll: true,
            });
        }, intervalMs);

        return () => window.clearInterval(interval);
    }, [hasGeneratingIdeas, intervalMs]);
}

import { useEffect, useState } from 'react';

const messages = [
    'Understanding the idea',
    'Defining the target user',
    'Shaping the MVP scope',
    'Planning execution phases',
    'Preparing the roadmap',
];

export default function GenerationProgressText({ intervalMs = 2200 }) {
    const [messageIndex, setMessageIndex] = useState(0);

    useEffect(() => {
        const interval = window.setInterval(() => {
            setMessageIndex((current) => (current + 1) % messages.length);
        }, intervalMs);

        return () => window.clearInterval(interval);
    }, [intervalMs]);

    return (
        <p className="text-sm font-medium text-teal-100" aria-live="polite">
            {messages[messageIndex]}
            <span className="ml-1 inline-flex w-5 justify-start text-teal-300">
                <AnimatedDots />
            </span>
        </p>
    );
}

function AnimatedDots() {
    const [count, setCount] = useState(1);

    useEffect(() => {
        const interval = window.setInterval(() => {
            setCount((current) => (current % 3) + 1);
        }, 450);

        return () => window.clearInterval(interval);
    }, []);

    return '.'.repeat(count);
}

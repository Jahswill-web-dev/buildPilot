export default function ErrorSummary({ errors }) {
    const messages = Object.values(errors || {}).filter(Boolean);

    if (!messages.length) {
        return null;
    }

    return (
        <div className="rounded-lg border border-red-500/30 bg-red-500/10 p-4">
            <ul className="list-inside list-disc space-y-1 text-sm text-red-300">
                {messages.map((message) => (
                    <li key={message}>{message}</li>
                ))}
            </ul>
        </div>
    );
}

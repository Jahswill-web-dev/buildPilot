export default function FormField({ label, error, children }) {
    return (
        <label className="block">
            <span className="mb-1.5 block text-sm font-medium text-zinc-300">{label}</span>
            {children}
            {error ? <span className="mt-1.5 block text-sm text-red-300">{error}</span> : null}
        </label>
    );
}

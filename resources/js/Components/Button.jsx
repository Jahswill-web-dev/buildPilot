export default function Button({ children, className = '', variant = 'primary', processing = false, ...props }) {
    const variants = {
        primary: 'bg-teal-600 text-white shadow-lg shadow-teal-950/40 hover:bg-teal-500 active:bg-teal-700',
        secondary: 'bg-white/10 text-white hover:bg-white/15',
        ghost: 'text-zinc-400 hover:bg-white/5 hover:text-white',
        danger: 'text-red-200 hover:bg-red-500/10 hover:text-red-100',
    };

    return (
        <button
            {...props}
            disabled={processing || props.disabled}
            className={`rounded-lg px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-zinc-950 disabled:cursor-not-allowed disabled:opacity-60 ${variants[variant]} ${className}`}
        >
            {processing ? 'Working...' : children}
        </button>
    );
}

export default function TextInput({ className = '', multiline = false, ...props }) {
    const classes = `block w-full rounded-lg border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-zinc-500 transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-teal-500 disabled:cursor-not-allowed disabled:opacity-60 ${className}`;

    if (multiline) {
        return <textarea {...props} className={`${classes} resize-y`} />;
    }

    return <input {...props} className={classes} />;
}

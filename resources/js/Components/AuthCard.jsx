import { Link } from '@inertiajs/react';

export default function AuthCard({ icon, title, subtitle, children, footerText, footerHref, footerAction }) {
    return (
        <div className="flex min-h-[calc(100vh-9rem)] items-center justify-center py-8">
            <section className="w-full max-w-md">
                <div className="mb-8 text-center">
                    <div className="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-lg border border-teal-500/30 bg-teal-500/15 text-teal-300">
                        {icon}
                    </div>
                    <h1 className="text-2xl font-semibold text-white">{title}</h1>
                    <p className="mt-1 text-sm text-zinc-400">{subtitle}</p>
                </div>

                <div className="rounded-lg border border-white/10 bg-white/[0.04] p-6 shadow-xl shadow-black/20 sm:p-8">
                    {children}

                    <div className="my-6 flex items-center">
                        <div className="flex-1 border-t border-white/10" />
                        <span className="px-3 text-xs text-zinc-500">or</span>
                        <div className="flex-1 border-t border-white/10" />
                    </div>

                    <p className="text-center text-sm text-zinc-400">
                        {footerText}{' '}
                        <Link href={footerHref} className="font-medium text-teal-300 transition hover:text-teal-200">
                            {footerAction}
                        </Link>
                    </p>
                </div>
            </section>
        </div>
    );
}

import { Link, router, usePage } from '@inertiajs/react';
import { Lightbulb, LogOut } from 'lucide-react';

export default function AppLayout({ title, children, maxWidth = 'max-w-4xl' }) {
    const { auth, flash } = usePage().props;

    return (
        <div className="min-h-screen bg-zinc-950 text-white">
            <nav className="sticky top-0 z-50 border-b border-white/10 bg-zinc-950/85 backdrop-blur-md">
                <div className={`mx-auto flex h-14 ${maxWidth} items-center justify-between px-4 sm:px-6`}>
                    <Link href="/" className="group flex items-center gap-2">
                        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600 transition group-hover:bg-teal-500">
                            <Lightbulb className="h-4 w-4 text-white" aria-hidden="true" />
                        </span>
                        <span className="text-sm font-semibold text-white">Idea Board</span>
                    </Link>

                    <div className="flex items-center gap-1">
                        {auth.user ? (
                            <>
                                <span className="mr-2 hidden max-w-36 truncate text-sm text-zinc-400 sm:block">
                                    {auth.user.name}
                                </span>
                                <NavLink href="/about">About</NavLink>
                                <NavLink href="/contact">Contact</NavLink>
                                <button
                                    type="button"
                                    id="logout-btn"
                                    onClick={() => router.post('/logout')}
                                    className="ml-1 inline-flex h-9 items-center gap-1 rounded-lg px-3 text-sm text-zinc-400 transition hover:bg-white/5 hover:text-white focus:outline-none focus:ring-2 focus:ring-teal-500"
                                >
                                    <LogOut className="h-4 w-4" aria-hidden="true" />
                                    <span className="hidden sm:inline">Log out</span>
                                </button>
                            </>
                        ) : (
                            <>
                                <NavLink href="/login">Sign in</NavLink>
                                <Link
                                    href="/register"
                                    id="nav-register"
                                    className="ml-1 rounded-lg bg-teal-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-zinc-950"
                                >
                                    Register
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </nav>

            {flash.success ? (
                <div className={`mx-auto mt-4 ${maxWidth} px-4 sm:px-6`}>
                    <div className="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                        {flash.success}
                    </div>
                </div>
            ) : null}

            <main className={`mx-auto ${maxWidth} px-4 py-8 sm:px-6`}>
                {title ? (
                    <div className="mb-8">
                        <h1 className="text-2xl font-semibold text-white">{title}</h1>
                    </div>
                ) : null}
                {children}
            </main>
        </div>
    );
}

function NavLink({ href, children }) {
    return (
        <Link
            href={href}
            className="rounded-lg px-3 py-1.5 text-sm text-zinc-400 transition hover:bg-white/5 hover:text-white focus:outline-none focus:ring-2 focus:ring-teal-500"
        >
            {children}
        </Link>
    );
}

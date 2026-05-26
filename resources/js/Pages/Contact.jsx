import { Head } from '@inertiajs/react';
import { Mail, MessageSquare } from 'lucide-react';
import AppLayout from '../Components/AppLayout';

export default function Contact() {
    return (
        <AppLayout title="Contact" maxWidth="max-w-4xl">
            <Head title="Contact us" />
            <div className="grid gap-6 md:grid-cols-[0.9fr_1.1fr]">
                <section className="rounded-lg border border-white/10 bg-white/[0.04] p-6">
                    <div className="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-teal-500/15 text-teal-300">
                        <MessageSquare className="h-5 w-5" aria-hidden="true" />
                    </div>
                    <h2 className="text-lg font-semibold text-white">Have a question?</h2>
                    <p className="mt-2 text-sm leading-6 text-zinc-400">
                        Reach out when you want to discuss the product direction, report an issue, or shape the next version of the idea workflow.
                    </p>
                </section>

                <section className="rounded-lg border border-white/10 bg-white/[0.035] p-6">
                    <div className="flex items-start gap-3">
                        <span className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg border border-white/10 bg-white/5 text-zinc-300">
                            <Mail className="h-4 w-4" aria-hidden="true" />
                        </span>
                        <div>
                            <h2 className="text-sm font-semibold text-white">Project inbox</h2>
                            <p className="mt-1 text-sm text-zinc-400">hello@ideaboard.local</p>
                        </div>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}

import { Head } from '@inertiajs/react';
import { Lightbulb, ListChecks, ShieldCheck } from 'lucide-react';
import AppLayout from '../Components/AppLayout';

export default function About() {
    return (
        <AppLayout title="About BuildPilot" maxWidth="max-w-4xl">
            <Head title="About us" />
            <div className="grid gap-6 md:grid-cols-[1.2fr_0.8fr]">
                <section className="rounded-lg border border-white/10 bg-white/[0.04] p-6">
                    <p className="text-sm leading-6 text-zinc-300">
                        BuildPilot helps you capture a rough thought, shape it into a practical checklist, and keep the next action visible.
                    </p>
                    <p className="mt-4 text-sm leading-6 text-zinc-400">
                        The app stays intentionally quiet: one place for the idea, the reasoning, and the work needed to move it forward.
                    </p>
                </section>

                <section className="space-y-3">
                    <AboutPoint icon={<Lightbulb />} title="Capture" text="Save ideas before the details disappear." />
                    <AboutPoint icon={<ListChecks />} title="Clarify" text="Turn each idea into a checklist you can edit." />
                    <AboutPoint icon={<ShieldCheck />} title="Private" text="Your account only shows the ideas you own." />
                </section>
            </div>
        </AppLayout>
    );
}

function AboutPoint({ icon, title, text }) {
    return (
        <div className="rounded-lg border border-white/10 bg-white/[0.035] p-4">
            <div className="mb-2 flex items-center gap-2 text-teal-300">
                <span className="[&>svg]:h-4 [&>svg]:w-4">{icon}</span>
                <h2 className="text-sm font-semibold text-white">{title}</h2>
            </div>
            <p className="text-sm leading-5 text-zinc-400">{text}</p>
        </div>
    );
}

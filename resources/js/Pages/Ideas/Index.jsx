import { Head, useForm } from '@inertiajs/react';
import { Lightbulb } from 'lucide-react';
import AppLayout from '../../Components/AppLayout';
import Button from '../../Components/Button';
import ErrorSummary from '../../Components/ErrorSummary';
import FormField from '../../Components/FormField';
import GeneratingIdeaCard from '../../Components/GeneratingIdeaCard';
import IdeaCard from '../../Components/IdeaCard';
import TextInput from '../../Components/TextInput';
import useGeneratingIdeasPoll from '../../hooks/useGeneratingIdeasPoll';

export default function Index({ ideas }) {
    useGeneratingIdeasPoll(ideas);

    const form = useForm({
        name: '',
        description: '',
    });

    const submit = (event) => {
        event.preventDefault();
        form.post('/ideas', {
            onSuccess: () => form.reset(),
        });
    };

    return (
        <AppLayout maxWidth="max-w-4xl">
            <Head title="My Ideas" />

            <header className="mb-8">
                <h1 className="text-2xl font-semibold text-white">My Ideas</h1>
                <p className="mt-1 text-sm text-zinc-400">Capture thoughts before they slip away.</p>
            </header>

            <section className="mb-8 rounded-lg border border-white/10 bg-white/[0.04] p-6 shadow-lg shadow-black/20">
                <form onSubmit={submit} className="space-y-5">
                    <ErrorSummary errors={form.errors} />

                    <FormField label="Idea name" error={form.errors.name}>
                        <TextInput
                            id="name"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            placeholder="Launch planner"
                            disabled={form.processing}
                        />
                    </FormField>

                    <FormField label="Description" error={form.errors.description}>
                        <TextInput
                            id="description"
                            multiline
                            rows="4"
                            value={form.data.description}
                            onChange={(event) => form.setData('description', event.target.value)}
                            placeholder="Describe the idea and why it matters."
                            disabled={form.processing}
                        />
                    </FormField>

                    <div className="flex justify-end">
                        <Button type="submit" id="generate-roadmap-btn" processing={form.processing}>
                            Generate Roadmap
                        </Button>
                    </div>
                </form>
            </section>

            {ideas.length ? (
                <section>
                    <h2 className="mb-3 text-xs font-semibold uppercase tracking-widest text-zinc-500">
                        Your Ideas ({ideas.length})
                    </h2>
                    <div className="space-y-3">
                        {ideas.map((idea) => (
                            idea.state === 'generating' ? (
                                <GeneratingIdeaCard key={idea.id} idea={idea} />
                            ) : (
                                <IdeaCard key={idea.id} idea={idea} />
                            )
                        ))}
                    </div>
                </section>
            ) : (
                <section className="py-16 text-center">
                    <div className="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-lg border border-white/10 bg-white/5">
                        <Lightbulb className="h-7 w-7 text-zinc-600" aria-hidden="true" />
                    </div>
                    <p className="text-sm text-zinc-500">No ideas yet. Write your first one above.</p>
                </section>
            )}
        </AppLayout>
    );
}

import { Head, useForm } from '@inertiajs/react';
import { LockKeyhole } from 'lucide-react';
import AppLayout from '../../Components/AppLayout';
import AuthCard from '../../Components/AuthCard';
import Button from '../../Components/Button';
import ErrorSummary from '../../Components/ErrorSummary';
import FormField from '../../Components/FormField';
import TextInput from '../../Components/TextInput';

export default function Login() {
    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event) => {
        event.preventDefault();
        form.post('/login', {
            onFinish: () => form.setData('password', ''),
        });
    };

    return (
        <AppLayout maxWidth="max-w-4xl">
            <Head title="Sign In" />
            <AuthCard
                icon={<LockKeyhole className="h-7 w-7" aria-hidden="true" />}
                title="Welcome back"
                subtitle="Sign in to access your ideas"
                footerText="Don't have an account?"
                footerHref="/register"
                footerAction="Create one"
            >
                <form onSubmit={submit} className="space-y-5">
                    <ErrorSummary errors={form.errors} />

                    <FormField label="Email address" error={form.errors.email}>
                        <TextInput
                            type="email"
                            id="email"
                            value={form.data.email}
                            onChange={(event) => form.setData('email', event.target.value)}
                            autoComplete="email"
                            required
                            autoFocus
                            placeholder="you@example.com"
                            disabled={form.processing}
                        />
                    </FormField>

                    <FormField label="Password" error={form.errors.password}>
                        <TextInput
                            type="password"
                            id="password"
                            value={form.data.password}
                            onChange={(event) => form.setData('password', event.target.value)}
                            autoComplete="current-password"
                            required
                            placeholder="Your password"
                            disabled={form.processing}
                        />
                    </FormField>

                    <label className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            id="remember"
                            checked={form.data.remember}
                            onChange={(event) => form.setData('remember', event.target.checked)}
                            className="h-4 w-4 cursor-pointer rounded border-white/20 bg-white/5 text-teal-500 focus:ring-teal-500 focus:ring-offset-0"
                        />
                        <span className="cursor-pointer text-sm text-zinc-400">Remember me</span>
                    </label>

                    <Button type="submit" id="login-submit" className="w-full py-2.5" processing={form.processing}>
                        Sign in
                    </Button>
                </form>
            </AuthCard>
        </AppLayout>
    );
}

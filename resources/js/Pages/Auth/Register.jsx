import { Head, useForm } from '@inertiajs/react';
import { UserPlus } from 'lucide-react';
import AppLayout from '../../Components/AppLayout';
import AuthCard from '../../Components/AuthCard';
import Button from '../../Components/Button';
import ErrorSummary from '../../Components/ErrorSummary';
import FormField from '../../Components/FormField';
import TextInput from '../../Components/TextInput';

export default function Register() {
    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event) => {
        event.preventDefault();
        form.post('/register', {
            onFinish: () => form.setData({
                ...form.data,
                password: '',
                password_confirmation: '',
            }),
        });
    };

    return (
        <AppLayout maxWidth="max-w-4xl">
            <Head title="Create Account" />
            <AuthCard
                icon={<UserPlus className="h-7 w-7" aria-hidden="true" />}
                title="Create your account"
                subtitle="Start saving your ideas today"
                footerText="Already have an account?"
                footerHref="/login"
                footerAction="Sign in"
            >
                <form onSubmit={submit} className="space-y-5">
                    <ErrorSummary errors={form.errors} />

                    <FormField label="Full name" error={form.errors.name}>
                        <TextInput
                            type="text"
                            id="name"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            autoComplete="name"
                            required
                            autoFocus
                            placeholder="John Doe"
                            disabled={form.processing}
                        />
                    </FormField>

                    <FormField label="Email address" error={form.errors.email}>
                        <TextInput
                            type="email"
                            id="email"
                            value={form.data.email}
                            onChange={(event) => form.setData('email', event.target.value)}
                            autoComplete="email"
                            required
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
                            autoComplete="new-password"
                            required
                            placeholder="At least 8 characters"
                            disabled={form.processing}
                        />
                    </FormField>

                    <FormField label="Confirm password" error={form.errors.password_confirmation}>
                        <TextInput
                            type="password"
                            id="password_confirmation"
                            value={form.data.password_confirmation}
                            onChange={(event) => form.setData('password_confirmation', event.target.value)}
                            autoComplete="new-password"
                            required
                            placeholder="Repeat your password"
                            disabled={form.processing}
                        />
                    </FormField>

                    <Button type="submit" id="register-submit" className="w-full py-2.5" processing={form.processing}>
                        Create account
                    </Button>
                </form>
            </AuthCard>
        </AppLayout>
    );
}

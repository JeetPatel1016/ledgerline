import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Layout, { buttonClass, Card, FieldError, inputClass } from '../components/Layout';

export default function Login() {
    const form = useForm({ email: '', password: '' });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/login', { onFinish: () => form.reset('password') });
    }

    return (
        <Layout title="Log in">
            <div className="mx-auto max-w-sm">
                <Card title="Log in">
                    <form onSubmit={submit} className="space-y-4">
                        <label className="block text-sm font-medium text-slate-700">
                            Email
                            <input
                                type="email"
                                autoComplete="username"
                                className={inputClass}
                                value={form.data.email}
                                onChange={(e) => form.setData('email', e.target.value)}
                            />
                            <FieldError message={form.errors.email} />
                        </label>
                        <label className="block text-sm font-medium text-slate-700">
                            Password
                            <input
                                type="password"
                                autoComplete="current-password"
                                className={inputClass}
                                value={form.data.password}
                                onChange={(e) => form.setData('password', e.target.value)}
                            />
                            <FieldError message={form.errors.password} />
                        </label>
                        <button type="submit" className={buttonClass} disabled={form.processing}>
                            Log in
                        </button>
                    </form>
                    <p className="mt-4 text-xs text-slate-500">Seeded demo user: test@example.com / password</p>
                </Card>
            </div>
        </Layout>
    );
}

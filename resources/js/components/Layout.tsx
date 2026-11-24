import { Head, Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { SharedProps } from '../types/ledger';

export default function Layout({ title, children }: { title: string; children: ReactNode }) {
    const { appName, auth, flash } = usePage<SharedProps>().props;

    return (
        <>
            <Head title={title} />
            <header className="border-b border-slate-200 bg-white">
                <div className="mx-auto flex max-w-5xl items-center justify-between px-6 py-3">
                    <Link href="/" className="font-semibold tracking-tight text-slate-900">
                        {appName}
                    </Link>
                    {auth.user && (
                        <div className="flex items-center gap-4 text-sm text-slate-600">
                            <span>{auth.user.email}</span>
                            <Link href="/logout" method="post" as="button" className="text-teal-700 hover:underline">
                                Log out
                            </Link>
                        </div>
                    )}
                </div>
            </header>
            <main className="mx-auto max-w-5xl space-y-8 px-6 py-8">
                {flash.success && (
                    <p role="status" className="rounded-md border border-teal-200 bg-teal-50 px-4 py-2 text-sm text-teal-800">
                        {flash.success}
                    </p>
                )}
                {children}
            </main>
        </>
    );
}

export function Card({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">{title}</h2>
            {children}
        </section>
    );
}

export function FieldError({ message }: { message?: string }) {
    return message ? <p className="mt-1 text-sm text-red-600">{message}</p> : null;
}

export const inputClass =
    'mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600';
export const buttonClass =
    'rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800 disabled:opacity-50';

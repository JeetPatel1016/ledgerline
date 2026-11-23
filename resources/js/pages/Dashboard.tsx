import { Head } from '@inertiajs/react';

interface DashboardProps {
    appName: string;
}

export default function Dashboard({ appName }: DashboardProps) {
    return (
        <>
            <Head title="Dashboard" />
            <main className="mx-auto flex min-h-screen max-w-3xl flex-col justify-center gap-4 px-6">
                <h1 className="text-3xl font-semibold tracking-tight text-slate-900">{appName}</h1>
                <p className="text-slate-600">
                    Double-entry ledger and payments API. The dashboard arrives in milestone 6.
                </p>
            </main>
        </>
    );
}

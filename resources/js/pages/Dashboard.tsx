import { Link } from '@inertiajs/react';
import CreateAccountForm from '../components/CreateAccountForm';
import Layout, { Card } from '../components/Layout';
import TransferForm from '../components/TransferForm';
import { formatMoney } from '../lib/money';
import type { Account } from '../types/ledger';

export default function Dashboard({ accounts }: { accounts: Account[] }) {
    return (
        <Layout title="Accounts">
            <Card title="Accounts">
                {accounts.length === 0 ? (
                    <p className="text-sm text-slate-500">No accounts yet. Create one below.</p>
                ) : (
                    <table className="w-full text-sm">
                        <thead className="text-left text-slate-500">
                            <tr>
                                <th className="py-2 font-medium">#</th>
                                <th className="py-2 font-medium">Name</th>
                                <th className="py-2 font-medium">Type</th>
                                <th className="py-2 text-right font-medium">Balance</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {accounts.map((account) => (
                                <tr key={account.id}>
                                    <td className="py-2 text-slate-400">{account.id}</td>
                                    <td className="py-2">
                                        <Link href={`/accounts/${account.id}`} className="text-teal-700 hover:underline">
                                            {account.name}
                                        </Link>
                                    </td>
                                    <td className="py-2 text-slate-600">
                                        {account.type}
                                        {account.allow_negative && <span className="ml-2 text-xs text-slate-400">may go negative</span>}
                                    </td>
                                    <td className="py-2 text-right tabular-nums">{formatMoney(account.balance)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </Card>
            <div className="grid gap-8 md:grid-cols-2">
                <Card title="New transfer">
                    <TransferForm accounts={accounts} />
                </Card>
                <Card title="New account">
                    <CreateAccountForm />
                </Card>
            </div>
        </Layout>
    );
}

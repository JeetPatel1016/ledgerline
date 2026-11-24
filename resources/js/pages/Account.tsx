import { Link } from '@inertiajs/react';
import Layout, { Card } from '../components/Layout';
import { formatMoney } from '../lib/money';
import type { Account as AccountData, Entry } from '../types/ledger';

type Props = {
    account: AccountData;
    entries: Entry[];
    nextCursor: string | null;
    prevCursor: string | null;
};

export default function Account({ account, entries, nextCursor, prevCursor }: Props) {
    const pageUrl = (cursor: string) => `/accounts/${account.id}?cursor=${encodeURIComponent(cursor)}`;

    return (
        <Layout title={account.name}>
            <div>
                <Link href="/" className="text-sm text-teal-700 hover:underline">
                    ← All accounts
                </Link>
                <div className="mt-2 flex items-end justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">{account.name}</h1>
                        <p className="text-sm text-slate-500">
                            #{account.id} · {account.type} · {account.currency}
                            {account.allow_negative && ' · may go negative'} · version {account.version}
                        </p>
                    </div>
                    <p className="text-2xl tabular-nums">{formatMoney(account.balance)}</p>
                </div>
            </div>
            <Card title="Entries">
                {entries.length === 0 ? (
                    <p className="text-sm text-slate-500">No entries yet. Transfers to or from this account show up here.</p>
                ) : (
                    <table className="w-full text-sm">
                        <thead className="text-left text-slate-500">
                            <tr>
                                <th className="py-2 font-medium">When</th>
                                <th className="py-2 font-medium">Transaction</th>
                                <th className="py-2 font-medium">Direction</th>
                                <th className="py-2 text-right font-medium">Amount</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {entries.map((entry) => (
                                <tr key={entry.id}>
                                    <td className="py-2 text-slate-600">{new Date(entry.created_at).toLocaleString()}</td>
                                    <td className="py-2 text-slate-600">#{entry.transaction_id}</td>
                                    <td className="py-2">{entry.direction}</td>
                                    <td className="py-2 text-right tabular-nums">{formatMoney(entry.amount)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
                {(prevCursor || nextCursor) && (
                    <div className="mt-4 flex justify-between text-sm">
                        {prevCursor ? <Link href={pageUrl(prevCursor)} className="text-teal-700 hover:underline">← Newer</Link> : <span />}
                        {nextCursor && <Link href={pageUrl(nextCursor)} className="text-teal-700 hover:underline">Older →</Link>}
                    </div>
                )}
            </Card>
        </Layout>
    );
}

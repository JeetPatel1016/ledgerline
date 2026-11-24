import { useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { parseAmount } from '../lib/money';
import type { Account, SharedProps } from '../types/ledger';
import { buttonClass, FieldError, inputClass } from './Layout';

/**
 * Posts through the same idempotent flow as the API. The key stays the same until a
 * transfer succeeds, so a double submit or a retry can't move money twice.
 */
export default function TransferForm({ accounts }: { accounts: Account[] }) {
    const [idempotencyKey, setIdempotencyKey] = useState(() => crypto.randomUUID());
    const form = useForm({ from: '', to: '', amount: '', fee: '' });
    const transferError = usePage<SharedProps>().props.errors.transfer;
    const from = accounts.find((account) => String(account.id) === form.data.from);
    const destinations = accounts.filter((account) => account.id !== from?.id && account.currency === from?.currency);

    function submit(event: FormEvent) {
        event.preventDefault();
        const amount = parseAmount(form.data.amount);
        const fee = form.data.fee === '' ? 0 : parseAmount(form.data.fee);
        if (amount === null || fee === null) {
            form.setError(amount === null ? 'amount' : 'fee', 'Enter an amount like 12.50');
            return;
        }

        form.transform((data) => ({ from: Number(data.from), to: Number(data.to), amount, fee, currency: from?.currency }));
        form.post('/transfers', {
            headers: { 'Idempotency-Key': idempotencyKey },
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setIdempotencyKey(crypto.randomUUID());
            },
        });
    }

    return (
        <form onSubmit={submit} className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
                <label className="block text-sm font-medium text-slate-700">
                    From
                    <select className={inputClass} value={form.data.from} onChange={(e) => form.setData('from', e.target.value)}>
                        <option value="">Choose…</option>
                        {accounts.map((account) => (
                            <option key={account.id} value={account.id}>
                                #{account.id} {account.name} ({account.currency})
                            </option>
                        ))}
                    </select>
                    <FieldError message={form.errors.from} />
                </label>
                <label className="block text-sm font-medium text-slate-700">
                    To
                    <select className={inputClass} value={form.data.to} onChange={(e) => form.setData('to', e.target.value)}>
                        <option value="">{from ? 'Choose…' : 'Pick "from" first'}</option>
                        {destinations.map((account) => (
                            <option key={account.id} value={account.id}>
                                #{account.id} {account.name}
                            </option>
                        ))}
                    </select>
                    <FieldError message={form.errors.to} />
                </label>
                <label className="block text-sm font-medium text-slate-700">
                    Amount {from && <span className="text-slate-400">({from.currency})</span>}
                    <input
                        inputMode="decimal"
                        placeholder="12.50"
                        className={inputClass}
                        value={form.data.amount}
                        onChange={(e) => form.setData('amount', e.target.value)}
                    />
                    <FieldError message={form.errors.amount} />
                </label>
                <label className="block text-sm font-medium text-slate-700">
                    Fee <span className="text-slate-400">(optional)</span>
                    <input
                        inputMode="decimal"
                        placeholder="0.00"
                        className={inputClass}
                        value={form.data.fee}
                        onChange={(e) => form.setData('fee', e.target.value)}
                    />
                    <FieldError message={form.errors.fee} />
                </label>
            </div>
            <FieldError message={transferError} />
            <div className="flex items-center justify-between gap-4">
                <button type="submit" className={buttonClass} disabled={form.processing}>
                    Send transfer
                </button>
                <span className="truncate font-mono text-xs text-slate-400" title="Idempotency-Key sent with this transfer">
                    key {idempotencyKey}
                </span>
            </div>
        </form>
    );
}

import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import type { AccountType, Currency } from '../types/ledger';
import { buttonClass, FieldError, inputClass } from './Layout';

const types: AccountType[] = ['liability', 'asset', 'equity', 'revenue', 'expense'];
const currencies: Currency[] = ['CAD', 'USD'];

export default function CreateAccountForm() {
    const form = useForm({ name: '', type: 'liability' as AccountType, currency: 'CAD' as Currency, allow_negative: false });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/accounts', { preserveScroll: true, onSuccess: () => form.reset() });
    }

    return (
        <form onSubmit={submit} className="space-y-4">
            <label className="block text-sm font-medium text-slate-700">
                Name
                <input className={inputClass} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                <FieldError message={form.errors.name} />
            </label>
            <div className="grid grid-cols-2 gap-4">
                <label className="block text-sm font-medium text-slate-700">
                    Type
                    <select
                        className={inputClass}
                        value={form.data.type}
                        onChange={(e) => form.setData('type', e.target.value as AccountType)}
                    >
                        {types.map((type) => (
                            <option key={type}>{type}</option>
                        ))}
                    </select>
                </label>
                <label className="block text-sm font-medium text-slate-700">
                    Currency
                    <select
                        className={inputClass}
                        value={form.data.currency}
                        onChange={(e) => form.setData('currency', e.target.value as Currency)}
                    >
                        {currencies.map((currency) => (
                            <option key={currency}>{currency}</option>
                        ))}
                    </select>
                </label>
            </div>
            <label className="flex items-center gap-2 text-sm text-slate-700">
                <input
                    type="checkbox"
                    checked={form.data.allow_negative}
                    onChange={(e) => form.setData('allow_negative', e.target.checked)}
                />
                Allow negative balance (e.g. a bank or funding account)
            </label>
            <button type="submit" className={buttonClass} disabled={form.processing}>
                Create account
            </button>
        </form>
    );
}

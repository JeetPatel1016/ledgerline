import type { Money } from '../types/ledger';

export function formatMoney({ amount, currency }: Money): string {
    return new Intl.NumberFormat('en-CA', { style: 'currency', currency }).format(amount / 100);
}

/** "12.50" → 1250 cents, without going through floats. Null if it isn't a valid amount. */
export function parseAmount(input: string): number | null {
    const match = /^(\d+)(?:\.(\d{1,2}))?$/.exec(input.trim());
    if (!match) {
        return null;
    }
    return Number(match[1]) * 100 + Number((match[2] ?? '').padEnd(2, '0'));
}

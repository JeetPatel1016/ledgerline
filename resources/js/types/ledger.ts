export type Currency = 'CAD' | 'USD';
export type AccountType = 'asset' | 'liability' | 'equity' | 'revenue' | 'expense';
export type Direction = 'debit' | 'credit';

/** Amounts are integers in minor units (cents). Format only for display. */
export type Money = { amount: number; currency: Currency };

export type Account = {
    id: number;
    name: string;
    type: AccountType;
    currency: Currency;
    allow_negative: boolean;
    balance: Money;
    version: number;
    created_at: string;
};

export type Entry = {
    id: number;
    transaction_id: number;
    account_id: number;
    direction: Direction;
    amount: Money;
    created_at: string;
};

export type SharedProps = {
    appName: string;
    auth: { user: { name: string; email: string } | null };
    flash: { success: string | null };
    errors: Record<string, string>;
};

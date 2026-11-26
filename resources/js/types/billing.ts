export interface CreditPurchase {
    id: number;
    amount: string;
    created_at: string;
}

export type TransactionKind = 'purchase' | 'hold' | 'release' | 'charge';

/** One line on the Reports page. `amount` is signed: "+$10.0000" or "−$0.0961". */
export interface Transaction {
    id: string;
    kind: TransactionKind;
    label: string;
    amount: string;
    /** The image the line belongs to; null for credit purchases. */
    generation_id: string | null;
    prompt: string | null;
    created_at: string;
}

export interface CreditPreset {
    value: number;
    label: string;
}

export type ReconciliationStatus = 'matched' | 'mismatched' | 'pending' | 'unavailable';

/** One UTC day; the pixelforge_* fields are null when /v1/usage could not be loaded. */
export interface ReconciliationDay {
    date: string;
    status: ReconciliationStatus;
    generations: number;
    pixelforge_generations: number | null;
    charged: string;
    provider_cost: string;
    pixelforge_cost: string | null;
    /** Pixelforge's cost minus ours, to the nanodollar. */
    cost_difference: string | null;
    tokens_match: boolean | null;
    margin: string;
}

export interface UserSpend {
    id: number;
    name: string;
    email: string;
    generations: number;
    charged: string;
    provider_cost: string;
    margin: string;
}

export interface ReconciliationReport {
    start_date: string;
    end_date: string;
    pixelforge_available: boolean;
    totals: {
        charged: string;
        provider_cost: string;
        pixelforge_cost: string | null;
        margin: string;
        mismatched_days: number;
    };
    days: ReconciliationDay[];
    users: UserSpend[];
}

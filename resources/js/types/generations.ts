/** A generation as GenerationResource sends it. Money arrives already formatted. */
export type GenerationStatus = 'queued' | 'processing' | 'succeeded' | 'failed';

export interface GenerationUsage {
    input_text_tokens: number;
    input_image_tokens: number;
    output_tokens: number;
    reasoning_tokens: number;
}

export interface Generation {
    id: string;
    prompt: string;
    size: string;
    quality: string;
    status: GenerationStatus;
    is_waiting_for_slot: boolean;
    is_finished: boolean;
    reference_images?: string[];
    estimated_charge: string;
    /** The amount reserved from the balance while it ran (the maximum plus a safety margin). */
    held_charge: string | null;
    actual_charge: string | null;
    image_url: string | null;
    image_expired: boolean;
    error_message: string | null;
    usage: GenerationUsage | null;
    created_at: string;
    completed_at: string | null;
}

/** The live estimate from POST /generations/quote. */
export interface Quote {
    estimated_charge: string;
    max_charge: string;
    /** What is reserved on submit: the maximum plus a small safety margin. */
    held_charge: string;
    balance: string;
    /** Whether the balance covers held_charge. */
    can_afford: boolean;
}

export interface Option {
    value: string;
    label: string;
}

/** A Laravel paginator wrapped in a resource collection. */
export interface Paginated<T> {
    data: T[];
    links: { first: string | null; last: string | null; prev: string | null; next: string | null };
    meta: { current_page: number; last_page: number; per_page: number; from: number | null; to: number | null; total: number };
}

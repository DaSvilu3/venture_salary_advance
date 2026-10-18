export type AdvanceStatus =
    | 'requested'
    | 'approved'
    | 'rejected'
    | 'cancelled'
    | 'disbursing'
    | 'disbursement_failed'
    | 'disbursed'
    | 'collection_failed'
    | 'overdue'
    | 'collected'
    | 'written_off';

/** Amounts are integer baisa; dates are ISO 8601 strings. */
export type Advance = {
    id: number;
    reference: string;
    status: AdvanceStatus;
    amount: number;
    fee: number;
    total_due: number;
    due_date: string;
    requested_at: string;
    disbursed_at: string | null;
    collected_at: string | null;
    collection_attempts: number;
    next_collection_attempt_on: string | null;
    failure_reason: string | null;
    employee?: { id: number; name: string; staff_no: string };
    employer?: { id: number; name: string };
};

export type Eligibility = {
    eligible: boolean;
    reasons: { code: string; message: string }[];
    limit: number;
    drawn: number;
    available: number;
    minimum: number;
    fee: number;
    payday: string;
    last_request_date: string;
    draws_this_cycle: number;
    max_draws: number;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
        per_page: number;
        links: PaginationLink[];
    };
};

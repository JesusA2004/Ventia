export type User = {
    id: number;
    company_id: number | null;
    name: string;
    email: string;
    avatar?: string;
    is_active: boolean;
    role: string | null;
    roles: string[];
    permissions: string[];
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

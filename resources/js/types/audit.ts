export type AuditLog = {
    id: number;
    user_id: number | null;
    user_name: string;
    company_id: number | null;
    company_name?: string | null;
    branch_id: number | null;
    branch_name?: string | null;
    module: string;
    module_label: string;
    action: string;
    entity_type: string | null;
    entity_id: number | null;
    description: string;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    reason: string | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
};

export type AuditFilterOption = { value: string; label: string };

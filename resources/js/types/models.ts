export type Status = 'active' | 'inactive';

export type Company = {
    id: number;
    name: string;
    legal_name: string | null;
    tax_id: string | null;
    logo_path: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
    currency: string;
    timezone: string;
    primary_color: string | null;
    secondary_color: string | null;
    status: Status;
};

export type Branch = {
    id: number;
    name: string;
    code: string;
    address: string | null;
    phone: string | null;
    manager_id: number | null;
    manager_name?: string | null;
    status: Status;
    status_label: string;
    warehouses_count?: number;
    registers_count?: number;
    created_at: string;
};

export type WarehouseType =
    'general' | 'sales_floor' | 'storage' | 'returns' | 'damaged' | 'transit';

export type Warehouse = {
    id: number;
    branch_id: number;
    branch_name?: string;
    name: string;
    code: string;
    type: WarehouseType;
    type_label: string;
    allows_sale: boolean;
    status: Status;
    status_label: string;
    created_at: string;
};

export type CashRegister = {
    id: number;
    branch_id: number;
    branch_name?: string;
    warehouse_id: number | null;
    warehouse_name?: string | null;
    name: string;
    code: string;
    printer_name: string | null;
    has_cash_drawer: boolean;
    assigned_user_id: number | null;
    assigned_user_name?: string | null;
    status: Status;
    status_label: string;
    created_at: string;
};

export type ManagedUser = {
    id: number;
    company_id: number | null;
    name: string;
    email: string;
    is_active: boolean;
    role: string | null;
    roles: string[];
    permissions: string[];
    branch_ids?: number[];
    branches?: { id: number; name: string }[];
    created_at: string;
};

export type RoleSummary = {
    id: number;
    name: string;
    permissions_count: number;
    users_count: number;
    editable: boolean;
};

export type PermissionEntry = {
    name: string;
    label: string;
    description: string | null;
    granted: boolean;
};

export type PermissionGroup = {
    label: string;
    permissions: PermissionEntry[];
};

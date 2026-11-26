/** A permission as PermissionName::options() sends it. */
export interface PermissionOption {
    value: string;
    label: string;
    description: string;
}

/** A role as RoleResource sends it. Permissions are PermissionName values. */
export interface Role {
    id: number;
    name: string;
    is_built_in: boolean;
    permissions?: string[];
    users_count?: number;
    can_update: boolean;
}

/** A user as UserResource sends it. `permissions` lists only the ones given directly. */
export interface ManagedUser {
    id: number;
    name: string;
    email: string;
    roles?: Role[];
    permissions?: string[];
    is_current_user: boolean;
    can_update_access: boolean;
    created_at: string | null;
}

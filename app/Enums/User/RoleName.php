<?php

namespace App\Enums\User;

/**
 * The built-in roles. Everyone who signs up gets Customer, which opens the
 * normal pages. Admin only holds the staff tools, so the two don't overlap.
 *
 * The seeder resets these to the permissions below on every deploy, so they
 * can be given to users but not edited on the roles page. Admins can still add
 * their own roles there.
 */
enum RoleName: string
{
    case Admin = 'admin';
    case Customer = 'customer';

    /**
     * The name shown on the users and roles pages.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Customer => 'Customer',
        };
    }

    /**
     * The permissions this role gets.
     *
     * @return list<PermissionName>
     */
    public function permissions(): array
    {
        return match ($this) {
            // Staff tools only. An admin who also wants to make images needs Customer as well.
            self::Admin => [
                PermissionName::ViewAdminReport,
                PermissionName::ManageUsers,
                PermissionName::ManageRoles,
            ],
            self::Customer => [
                PermissionName::ViewDashboard,
                PermissionName::GenerateImages,
                PermissionName::ViewHistory,
                PermissionName::ManageCredits,
                PermissionName::ViewReports,
            ],
        };
    }
}

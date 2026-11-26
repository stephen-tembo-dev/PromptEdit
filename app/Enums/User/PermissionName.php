<?php

namespace App\Enums\User;

/**
 * All the permissions the app checks. RolesAndPermissionsSeeder saves one row for each.
 * They live in code because the code is what checks them: a permission made up
 * on the users page would not unlock anything.
 */
enum PermissionName: string
{
    case ViewDashboard = 'view dashboard';
    case GenerateImages = 'generate images';
    case ViewHistory = 'view history';
    case ManageCredits = 'manage credits';
    case ViewReports = 'view reports';
    case ViewAdminReport = 'view admin report';
    case ManageUsers = 'manage users';
    case ManageRoles = 'manage roles';

    /**
     * The name shown on the users and roles pages.
     */
    public function label(): string
    {
        return match ($this) {
            self::ViewDashboard => 'View dashboard',
            self::GenerateImages => 'Generate images',
            self::ViewHistory => 'View history',
            self::ManageCredits => 'Manage credits',
            self::ViewReports => 'View reports',
            self::ViewAdminReport => 'View admin report',
            self::ManageUsers => 'Manage users',
            self::ManageRoles => 'Manage roles',
        };
    }

    /**
     * One line on what the permission unlocks, shown next to its checkbox.
     */
    public function description(): string
    {
        return match ($this) {
            self::ViewDashboard => 'Open the dashboard.',
            self::GenerateImages => 'Use the Generate page to get a price and send new images, paid from their balance.',
            self::ViewHistory => 'See the History page and each of their own images.',
            self::ManageCredits => 'See their balance and purchases, and buy credits.',
            self::ViewReports => 'See every credit they bought and every hold, charge and release for their images.',
            self::ViewAdminReport => 'See revenue, Pixelforge cost and margin for every user, checked against Pixelforge\'s usage.',
            self::ManageUsers => 'Create accounts and change which roles and permissions other users have.',
            self::ManageRoles => 'Create, rename and delete roles, and choose their permissions.',
        };
    }

    /**
     * Every permission as a checkbox for the users and roles pages.
     *
     * @return list<array{value: string, label: string, description: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $permission): array => [
            'value' => $permission->value,
            'label' => $permission->label(),
            'description' => $permission->description(),
        ], self::cases());
    }
}

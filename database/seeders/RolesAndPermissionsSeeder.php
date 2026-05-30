<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    private const array RolePermissionMatrix = [
        'front-officer' => [
            'dashboard.view',
            'loans.view',
            'loans.create',
            'devices.view',
            'staff.view',
        ],
        'supervisor' => [
            'dashboard.view',
            'loans.view',
            'loans.create',
            'loans.approve',
            'devices.view',
            'returned_devices.view',
            'staff.view',
            'reports.view',
        ],
        'manager' => [
            'dashboard.view',
            'loans.view',
            'loans.create',
            'loans.approve',
            'loans.export',
            'devices.view',
            'returned_devices.view',
            'staff.view',
            'reports.view',
            'reports.export',
        ],
        'back-officer' => [
            'dashboard.view',
            'loans.view',
            'loans.create',
            'loans.edit',
            'devices.view',
            'staff.view',
            'accounting.view',
        ],
        'accountant' => [
            'dashboard.view',
            'accounting.view',
            'accounting.export',
            'reports.view',
            'reports.export',
            'reconciliation.view',
        ],
        'dealer' => [
            'dashboard.view',
            'loans.view',
            'devices.view',
            'sales.view',
        ],
        'owner' => [
            'dashboard.all',
            'loans.all',
            'devices.all',
            'staff.all',
            'reports.all',
            'access.all',
            'settings.all',
        ],
        'admin' => [
            'dashboard.all',
            'loans.all',
            'devices.all',
            'staff.all',
            'reports.all',
            'access.all',
            'settings.all',
            'accounting.all',
        ],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $modules = [
            'Dashboard', 'Accounting', 'SMS Campaign', 'Payment analytics', 'Loans',
            'Products', 'Calculator', 'Devices', 'Returned devices', 'Financial plans',
            'Staff', 'Dealers', 'Expenses', 'Reports', 'Sales',
            'Reconciliation', 'Access', 'Account', 'Settings',
        ];

        $actions = ['view', 'create', 'edit', 'delete', 'approve', 'export'];

        foreach ($modules as $mod) {
            $slug = strtolower(str_replace(' ', '_', $mod));

            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$slug}.{$action}", 'guard_name' => 'web']);
            }

            Permission::firstOrCreate(['name' => "{$slug}.all", 'guard_name' => 'web']);
        }

        $roles = [
            'accountant' => 'Accountant privileges',
            'back-officer' => 'BO Privileges',
            'front-officer' => 'Front-officer privileges',
            'supervisor' => 'Supervisor privileges',
            'dealer' => 'Dealer privilege',
            'manager' => 'Manager privileges',
            'owner' => 'Owner privileges',
            'admin' => 'Admin privileges',
        ];

        foreach ($roles as $name => $description) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            if (empty($role->description)) {
                $role->update(['description' => $description]);
            }
        }

        foreach (self::RolePermissionMatrix as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if (! $role) {
                continue;
            }

            $resolved = collect($permissionNames)
                ->flatMap(function (string $name): array {
                    if (str_ends_with($name, '.all')) {
                        $module = str_replace('.all', '', $name);

                        return Permission::query()
                            ->where('name', 'like', "{$module}.%")
                            ->pluck('name')
                            ->all();
                    }

                    return Permission::where('name', $name)->exists() ? [$name] : [];
                })
                ->unique()
                ->values()
                ->all();

            $role->syncPermissions($resolved);
        }
    }
}

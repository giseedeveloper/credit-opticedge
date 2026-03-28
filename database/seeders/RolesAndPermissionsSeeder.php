<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // High-resolution modules
        $modules = [
            'Dashboard', 'Accounting', 'SMS Campaign', 'Payment analytics', 'Loans',
            'Products', 'Calculator', 'Devices', 'Returned devices', 'Financial plans',
            'Staff', 'Vendors', 'Branches', 'Expenses', 'Reports', 'Sales',
            'Reconciliation', 'Access', 'Account', 'Settings',
        ];

        // Specific actions
        $actions = ['view', 'create', 'edit', 'delete', 'approve', 'export'];

        // Create Permissions
        foreach ($modules as $mod) {
            $slug = strtolower(str_replace(' ', '_', $mod));

            // Generic matrix
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$slug}.{$action}"]);
            }

            // "all" macro permission per module
            Permission::firstOrCreate(['name' => "{$slug}.all"]);
        }

        // Roles To Initialize
        $roles = [
            'accountant' => 'Accountant privileges',
            'back-officer' => 'BO Privileges',
            'front-officer' => 'Front-officer privileges',
            'supervisor' => 'Supervisor privileges',
            'vendor' => 'Vendor privilege',
            'manager' => 'Manager privileges',
            'owner' => 'Owner privileges',
            'admin' => 'Admin privileges',
        ];

        foreach ($roles as $name => $description) {
            $role = Role::firstOrCreate(['name' => $name]);
            if (empty($role->description)) {
                $role->update(['description' => $description]);
            }
        }

        // We can optionally assign initial grants here, but for now we rely on the API/UI.
    }
}

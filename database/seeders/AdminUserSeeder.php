<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class AdminUserSeeder extends Seeder
{
    /**
     * Creates the super-admin user and grants the admin role all permissions.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $hq = Branch::firstOrCreate(
            ['code' => 'HQ-001'],
            [
                'name' => 'OpticEdge HQ - Dar es Salaam',
                'region' => 'Dar es Salaam',
                'address' => 'Samora Avenue, Posta, Ilala',
                'phone' => '+255 22 211 0000',
                'is_headquarter' => true,
                'is_active' => true,
            ]
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@opticedge.co.tz'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin@2025!'),
                'email_verified_at' => now(),
                'phone' => '+255 700 000 000',
                'employee_code' => 'EMP-ADMIN-001',
                'branch_id' => $hq->id,
                'is_active' => true,
            ]
        );

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            ['description' => 'Admin privileges']
        );

        $adminRole->syncPermissions(Permission::all());

        $admin->syncRoles(['admin']);

        $this->command->info('✓ Admin user:  admin@opticedge.co.tz');
        $this->command->info('✓ Password:    Admin@2025!');
        $this->command->info('✓ Permissions: '.$adminRole->permissions()->count().' assigned to admin role');
    }
}

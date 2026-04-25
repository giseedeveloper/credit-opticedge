<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Rename the legacy Spatie role `vendor` to `dealer` (same row / UUID; pivots unchanged).
     */
    public function up(): void
    {
        $vendor = DB::table('roles')
            ->where('name', 'vendor')
            ->where('guard_name', 'web')
            ->first();

        if ($vendor === null) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            return;
        }

        $dealerExists = DB::table('roles')
            ->where('name', 'dealer')
            ->where('guard_name', 'web')
            ->exists();

        if ($dealerExists) {
            $dealerId = DB::table('roles')
                ->where('name', 'dealer')
                ->where('guard_name', 'web')
                ->value('id');

            foreach (DB::table('role_has_permissions')->where('role_id', $vendor->id)->cursor() as $link) {
                $already = DB::table('role_has_permissions')
                    ->where('role_id', $dealerId)
                    ->where('permission_id', $link->permission_id)
                    ->exists();
                if (! $already) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $link->permission_id,
                        'role_id' => $dealerId,
                    ]);
                }
            }

            DB::table('model_has_roles')
                ->where('role_id', $vendor->id)
                ->update(['role_id' => $dealerId]);

            DB::table('role_has_permissions')
                ->where('role_id', $vendor->id)
                ->delete();

            DB::table('roles')->where('id', $vendor->id)->delete();
        } else {
            DB::table('roles')
                ->where('id', $vendor->id)
                ->update([
                    'name' => 'dealer',
                    'description' => 'Dealer privilege',
                ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $dealer = DB::table('roles')
            ->where('name', 'dealer')
            ->where('guard_name', 'web')
            ->first();

        if ($dealer === null) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            return;
        }

        DB::table('roles')
            ->where('id', $dealer->id)
            ->update([
                'name' => 'vendor',
                'description' => 'Dealer privilege',
            ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};

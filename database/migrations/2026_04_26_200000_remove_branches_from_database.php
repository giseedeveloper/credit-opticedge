<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Remove branch tenancy: drop branch_id FKs/columns and the branches table.
     */
    public function up(): void
    {
        foreach (['inventory_units', 'loans', 'customers', 'dealers', 'users'] as $table) {
            $this->dropBranchIdColumn($table);
        }

        Schema::dropIfExists('branches');

        $permIds = DB::table('permissions')->where('name', 'like', 'branches.%')->pluck('id');
        foreach ($permIds as $id) {
            DB::table('role_has_permissions')->where('permission_id', $id)->delete();
            DB::table('model_has_permissions')->where('permission_id', $id)->delete();
        }
        DB::table('permissions')->whereIn('id', $permIds)->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function dropBranchIdColumn(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'branch_id')) {
            return;
        }

        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        $database = $connection->getDatabaseName();

        $fkNames = match ($driver) {
            'mysql' => collect($connection->select(
                'select CONSTRAINT_NAME as name from information_schema.KEY_COLUMN_USAGE '
                    .'where TABLE_SCHEMA = ? and TABLE_NAME = ? and COLUMN_NAME = ? and REFERENCED_TABLE_NAME is not null',
                [$database, $table, 'branch_id']
            ))->map(fn ($row) => is_array($row) ? $row['name'] : $row->name),
            'pgsql' => collect($connection->select(
                'select tc.constraint_name as name '
                    .'from information_schema.table_constraints tc '
                    .'inner join information_schema.key_column_usage kcu '
                    .'on tc.constraint_catalog = kcu.constraint_catalog '
                    .'and tc.constraint_schema = kcu.constraint_schema '
                    .'and tc.constraint_name = kcu.constraint_name '
                    .'where tc.table_catalog = ? and tc.table_schema = current_schema() '
                    .'and tc.table_name = ? and kcu.column_name = ? '
                    ."and tc.constraint_type = 'FOREIGN KEY'",
                [$database, $table, 'branch_id']
            ))->map(fn ($row) => is_array($row) ? $row['name'] : $row->name),
            default => collect(),
        };

        foreach ($fkNames as $name) {
            $name = (string) $name;
            if ($name === '') {
                continue;
            }
            if ($driver === 'mysql') {
                $connection->statement('ALTER TABLE `'.$table.'` DROP FOREIGN KEY `'.$name.'`');
            } elseif ($driver === 'pgsql') {
                $connection->statement('ALTER TABLE "'.$table.'" DROP CONSTRAINT "'.$name.'"');
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table): void {
            if (Schema::hasColumn($table, 'branch_id')) {
                $blueprint->dropColumn('branch_id');
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('This migration cannot be reversed; restore from backup if needed.');
    }
};

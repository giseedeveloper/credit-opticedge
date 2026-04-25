<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('permissions')->where('name', 'like', 'vendors.%')->cursor() as $row) {
            $newName = str_replace('vendors.', 'dealers.', (string) $row->name);
            if (DB::table('permissions')->where('name', $newName)->exists()) {
                continue;
            }
            DB::table('permissions')->where('id', $row->id)->update(['name' => $newName]);
        }
    }

    public function down(): void
    {
        foreach (DB::table('permissions')->where('name', 'like', 'dealers.%')->cursor() as $row) {
            $oldName = str_replace('dealers.', 'vendors.', (string) $row->name);
            if (DB::table('permissions')->where('name', $oldName)->exists()) {
                continue;
            }
            DB::table('permissions')->where('id', $row->id)->update(['name' => $oldName]);
        }
    }
};

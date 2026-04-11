<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $existing = Schema::getColumnListing('customers');

        Schema::table('customers', function (Blueprint $table) use ($existing): void {
            if (! in_array('phone_model_id', $existing, true)) {
                $table->foreignUuid('phone_model_id')
                    ->nullable()
                    ->after('vendor_id')
                    ->constrained('phone_models')
                    ->nullOnDelete();
            }

            if (! in_array('inventory_unit_id', $existing, true)) {
                $table->foreignUuid('inventory_unit_id')
                    ->nullable()
                    ->after('phone_model_id')
                    ->constrained('inventory_units')
                    ->nullOnDelete();
            }

            if (! in_array('device_scan_metadata', $existing, true)) {
                $table->json('device_scan_metadata')
                    ->nullable()
                    ->after('device_photo_path');
            }
        });
    }

    public function down(): void
    {
        $existing = Schema::getColumnListing('customers');

        Schema::table('customers', function (Blueprint $table) use ($existing): void {
            if (in_array('inventory_unit_id', $existing, true)) {
                $table->dropConstrainedForeignId('inventory_unit_id');
            }

            if (in_array('phone_model_id', $existing, true)) {
                $table->dropConstrainedForeignId('phone_model_id');
            }

            if (in_array('device_scan_metadata', $existing, true)) {
                $table->dropColumn('device_scan_metadata');
            }
        });
    }
};

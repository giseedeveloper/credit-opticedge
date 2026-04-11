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
            if (! in_array('phone_metadata', $existing, true)) {
                $table->json('phone_metadata')
                    ->nullable()
                    ->after('alt_phone');
            }

            if (! in_array('device_accessories', $existing, true)) {
                $table->json('device_accessories')
                    ->nullable()
                    ->after('device_scan_metadata');
            }

            if (! in_array('store_offer_notes', $existing, true)) {
                $table->text('store_offer_notes')
                    ->nullable()
                    ->after('device_accessories');
            }
        });
    }

    public function down(): void
    {
        $existing = Schema::getColumnListing('customers');

        Schema::table('customers', function (Blueprint $table) use ($existing): void {
            if (in_array('store_offer_notes', $existing, true)) {
                $table->dropColumn('store_offer_notes');
            }

            if (in_array('device_accessories', $existing, true)) {
                $table->dropColumn('device_accessories');
            }

            if (in_array('phone_metadata', $existing, true)) {
                $table->dropColumn('phone_metadata');
            }
        });
    }
};

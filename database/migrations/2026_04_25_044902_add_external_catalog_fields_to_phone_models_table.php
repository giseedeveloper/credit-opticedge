<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('phone_models', function (Blueprint $table) {
            if (! Schema::hasColumn('phone_models', 'external_source')) {
                $table->string('external_source', 40)->nullable()->after('specifications');
            }

            if (! Schema::hasColumn('phone_models', 'external_id')) {
                $table->string('external_id', 80)->nullable()->after('external_source');
            }

            if (! Schema::hasColumn('phone_models', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('external_id');
            }

            $table->index(['external_source', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phone_models', function (Blueprint $table) {
            $table->dropIndex(['external_source', 'external_id']);

            if (Schema::hasColumn('phone_models', 'last_synced_at')) {
                $table->dropColumn('last_synced_at');
            }

            if (Schema::hasColumn('phone_models', 'external_id')) {
                $table->dropColumn('external_id');
            }

            if (Schema::hasColumn('phone_models', 'external_source')) {
                $table->dropColumn('external_source');
            }
        });
    }
};

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
        $existing = Schema::getColumnListing('verifications');

        Schema::table('verifications', function (Blueprint $table) use ($existing) {
            if (! in_array('auto_check_status', $existing)) {
                $table->string('auto_check_status', 30)->nullable()->after('status');
            }
            if (! in_array('auto_check_results', $existing)) {
                $table->json('auto_check_results')->nullable()->after('auto_check_status');
            }
            if (! in_array('auto_check_ran_at', $existing)) {
                $table->timestamp('auto_check_ran_at')->nullable()->after('auto_check_results');
            }
            if (! in_array('fo_id', $existing)) {
                $table->foreignUuid('fo_id')->nullable()->constrained('users')->nullOnDelete()->after('auto_check_ran_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('verifications', function (Blueprint $table) {
            $table->dropColumn(['auto_check_status', 'auto_check_results', 'auto_check_ran_at', 'fo_id']);
        });
    }
};

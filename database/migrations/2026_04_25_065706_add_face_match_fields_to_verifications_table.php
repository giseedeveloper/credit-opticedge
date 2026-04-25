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
        Schema::table('verifications', function (Blueprint $table) {
            $table->string('face_match_status')->nullable()->after('auto_check_ran_at');
            $table->decimal('face_match_score', 5, 4)->nullable()->after('face_match_status');
            $table->string('face_match_reason')->nullable()->after('face_match_score');
            $table->timestamp('face_match_ran_at')->nullable()->after('face_match_reason');

            $table->foreignUuid('face_match_manual_verified_by')
                ->nullable()
                ->after('face_match_ran_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('face_match_manual_verified_at')->nullable()->after('face_match_manual_verified_by');

            $table->index(['face_match_status', 'face_match_ran_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('verifications', function (Blueprint $table) {
            $table->dropIndex(['face_match_status', 'face_match_ran_at']);
            $table->dropConstrainedForeignId('face_match_manual_verified_by');
            $table->dropColumn([
                'face_match_status',
                'face_match_score',
                'face_match_reason',
                'face_match_ran_at',
                'face_match_manual_verified_at',
            ]);
        });
    }
};

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
            if (! in_array('stage', $existing)) {
                $table->tinyInteger('stage')->default(1)->after('type');
            }
            if (! in_array('stage1_status', $existing)) {
                $table->string('stage1_status')->default('pending')->after('stage');
            }
            if (! in_array('stage2_status', $existing)) {
                $table->string('stage2_status')->default('pending')->after('stage1_status');
            }
            if (! in_array('stage3_status', $existing)) {
                $table->string('stage3_status')->default('pending')->after('stage2_status');
            }
            if (! in_array('stage4_status', $existing)) {
                $table->string('stage4_status')->default('pending')->after('stage3_status');
            }
            if (! in_array('stage1_reviewed_by', $existing)) {
                $table->foreignUuid('stage1_reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('stage4_status');
            }
            if (! in_array('stage1_reviewed_at', $existing)) {
                $table->timestamp('stage1_reviewed_at')->nullable()->after('stage1_reviewed_by');
            }
            if (! in_array('stage1_notes', $existing)) {
                $table->text('stage1_notes')->nullable()->after('stage1_reviewed_at');
            }
            if (! in_array('stage1_rejection_reason', $existing)) {
                $table->text('stage1_rejection_reason')->nullable()->after('stage1_notes');
            }
            if (! in_array('stage2_reviewed_by', $existing)) {
                $table->foreignUuid('stage2_reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('stage1_rejection_reason');
            }
            if (! in_array('stage2_reviewed_at', $existing)) {
                $table->timestamp('stage2_reviewed_at')->nullable()->after('stage2_reviewed_by');
            }
            if (! in_array('stage2_notes', $existing)) {
                $table->text('stage2_notes')->nullable()->after('stage2_reviewed_at');
            }
            if (! in_array('stage2_rejection_reason', $existing)) {
                $table->text('stage2_rejection_reason')->nullable()->after('stage2_notes');
            }
            if (! in_array('confirmation_call_outcome', $existing)) {
                $table->string('confirmation_call_outcome')->nullable()->after('stage2_rejection_reason');
            }
            if (! in_array('confirmation_call_notes', $existing)) {
                $table->text('confirmation_call_notes')->nullable()->after('confirmation_call_outcome');
            }
            if (! in_array('confirmation_called_at', $existing)) {
                $table->timestamp('confirmation_called_at')->nullable()->after('confirmation_call_notes');
            }
            if (! in_array('confirmation_called_by', $existing)) {
                $table->foreignUuid('confirmation_called_by')->nullable()->constrained('users')->nullOnDelete()->after('confirmation_called_at');
            }
            if (! in_array('nok_call_outcome', $existing)) {
                $table->string('nok_call_outcome')->nullable()->after('confirmation_called_by');
            }
            if (! in_array('nok_call_notes', $existing)) {
                $table->text('nok_call_notes')->nullable()->after('nok_call_outcome');
            }
            if (! in_array('nok_called_at', $existing)) {
                $table->timestamp('nok_called_at')->nullable()->after('nok_call_notes');
            }
            if (! in_array('nok_called_by', $existing)) {
                $table->foreignUuid('nok_called_by')->nullable()->constrained('users')->nullOnDelete()->after('nok_called_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('verifications', function (Blueprint $table) {
            $table->dropColumn([
                'stage', 'stage1_status', 'stage2_status', 'stage3_status', 'stage4_status',
                'stage1_reviewed_by', 'stage1_reviewed_at', 'stage1_notes', 'stage1_rejection_reason',
                'stage2_reviewed_by', 'stage2_reviewed_at', 'stage2_notes', 'stage2_rejection_reason',
                'confirmation_call_outcome', 'confirmation_call_notes', 'confirmation_called_at', 'confirmation_called_by',
                'nok_call_outcome', 'nok_call_notes', 'nok_called_at', 'nok_called_by',
            ]);
        });
    }
};

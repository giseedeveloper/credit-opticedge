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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('application_draft_reference')->nullable()->after('inventory_unit_id')->index();
            $table->foreignUuid('agreement_document_id')->nullable()->after('call_consent_accepted')->constrained('system_documents')->nullOnDelete();
            $table->boolean('agreement_accepted')->default(false)->after('agreement_document_id');
            $table->timestamp('agreement_presented_at')->nullable()->after('agreement_accepted');
            $table->timestamp('agreement_decision_at')->nullable()->after('agreement_presented_at');
            $table->string('customer_signature_path')->nullable()->after('agreement_decision_at');
            $table->string('fo_signature_path')->nullable()->after('customer_signature_path');
            $table->string('asset_handover_list_path')->nullable()->after('fo_signature_path');
            $table->text('asset_handover_notes')->nullable()->after('asset_handover_list_path');
            $table->string('deposit_payment_status', 40)->nullable()->after('asset_handover_notes')->index();
            $table->decimal('deposit_payment_amount', 15, 2)->nullable()->after('deposit_payment_status');
            $table->string('deposit_payment_reference')->nullable()->after('deposit_payment_amount');
            $table->timestamp('deposit_paid_at')->nullable()->after('deposit_payment_reference');
            $table->string('asset_release_status', 40)->default('pending')->after('deposit_paid_at')->index();
            $table->timestamp('asset_released_at')->nullable()->after('asset_release_status');
            $table->foreignUuid('asset_released_by')->nullable()->after('asset_released_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agreement_document_id');
            $table->dropConstrainedForeignId('asset_released_by');
            $table->dropColumn([
                'application_draft_reference',
                'agreement_accepted',
                'agreement_presented_at',
                'agreement_decision_at',
                'customer_signature_path',
                'fo_signature_path',
                'asset_handover_list_path',
                'asset_handover_notes',
                'deposit_payment_status',
                'deposit_payment_amount',
                'deposit_payment_reference',
                'deposit_paid_at',
                'asset_release_status',
                'asset_released_at',
            ]);
        });
    }
};

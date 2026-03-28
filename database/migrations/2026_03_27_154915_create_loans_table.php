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
        Schema::create('loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('customers');
            $table->foreignUuid('inventory_unit_id')->nullable()->constrained('inventory_units')->nullOnDelete();
            $table->foreignUuid('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignUuid('disbursed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('loan_number', 30)->unique();
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('deposit_paid', 15, 2)->default(0);
            $table->decimal('interest_rate', 5, 2);
            $table->string('interest_type')->default('flat');
            $table->decimal('total_debt', 15, 2);
            $table->decimal('total_payable', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('remaining_balance', 15, 2);
            $table->decimal('outstanding_balance', 15, 2);
            $table->decimal('penalty_amount', 15, 2)->default(0);
            $table->integer('duration_weeks');
            $table->integer('grace_period_days')->default(3);
            $table->string('repayment_frequency')->default('weekly');
            $table->string('status')->default('pending');
            $table->date('disbursed_at')->nullable();
            $table->date('due_date')->nullable();
            $table->date('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'customer_id']);
            $table->index(['due_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};

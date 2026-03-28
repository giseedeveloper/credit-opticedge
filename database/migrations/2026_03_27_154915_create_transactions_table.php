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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('loan_id')->nullable()->constrained('loans')->nullOnDelete();
            $table->foreignUuid('repayment_schedule_id')->nullable()->constrained('repayment_schedules')->nullOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference', 50)->unique();
            $table->string('type');
            $table->string('entry_type');
            $table->decimal('amount', 15, 2);
            $table->string('channel')->nullable();
            $table->string('external_reference')->nullable();
            $table->text('description')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamp('transacted_at');
            $table->timestamps();

            $table->index(['loan_id', 'type']);
            $table->index(['customer_id', 'transacted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

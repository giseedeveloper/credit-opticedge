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
        Schema::dropIfExists('recovery_tickets');

        Schema::create('recovery_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('loan_id')->index();
            $table->foreignUuid('assigned_agent_id')->nullable()->index(); // The Recovery Officer
            $table->string('status')->default('open'); // open, assigned, recovered, failed
            $table->decimal('reward_amount', 10, 2)->default(0); // Commission
            $table->text('notes')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('loan_id')->references('id')->on('loans')->cascadeOnDelete();
            $table->foreign('assigned_agent_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recovery_tickets');
    }
};

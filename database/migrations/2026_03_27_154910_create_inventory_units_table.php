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
        Schema::create('inventory_units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('phone_model_id')->constrained('phone_models');
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignUuid('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('imei_1', 20)->unique();
            $table->string('imei_2', 20)->nullable()->unique();
            $table->string('serial_number', 50)->nullable()->unique();
            $table->string('status')->default('available');
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->date('received_at')->nullable();
            $table->jsonb('extra_data')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'vendor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_units');
    }
};

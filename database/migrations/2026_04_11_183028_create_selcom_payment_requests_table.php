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
        Schema::create('selcom_payment_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('draft_reference')->index();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignUuid('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_id')->unique();
            $table->string('transid')->unique();
            $table->string('phone', 32);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('TZS');
            $table->string('provider', 40)->default('wallet-payment');
            $table->string('channel', 40)->nullable();
            $table->string('status', 40)->default('initiated')->index();
            $table->string('payment_status', 40)->nullable()->index();
            $table->string('result', 40)->nullable();
            $table->string('resultcode', 20)->nullable();
            $table->string('selcom_reference')->nullable()->index();
            $table->string('gateway_buyer_uuid')->nullable();
            $table->string('payment_token')->nullable();
            $table->text('payment_gateway_url')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('status_payload')->nullable();
            $table->json('webhook_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('webhook_received_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('selcom_payment_requests');
    }
};

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
        Schema::table('commission_ledgers', function (Blueprint $table) {
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->foreign('loan_id')->references('id')->on('loans')->nullOnDelete();
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_ledgers', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropForeign(['loan_id']);
            $table->dropForeign(['transaction_id']);
        });
    }
};

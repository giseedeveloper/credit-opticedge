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
        if (!collect(\Illuminate\Support\Facades\DB::select("SELECT indexname FROM pg_indexes WHERE tablename='loans' AND indexname='loans_loan_number_unique'"))->isNotEmpty()) {
            Schema::table('loans', function (Blueprint $table) {
                $table->unique('loan_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropUnique(['loan_number']);
        });
    }
};

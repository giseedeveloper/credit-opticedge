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
        Schema::table('loans', function (Blueprint $table) {
            $table->integer('dpd')->default(0)->index()->after('status'); // Days Past Due
            $table->tinyInteger('ifrs_stage')->default(1)->index()->after('dpd'); // 1, 2, or 3
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['dpd', 'ifrs_stage']);
        });
    }
};

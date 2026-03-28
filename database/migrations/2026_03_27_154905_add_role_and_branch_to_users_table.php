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
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('branch_id')->nullable()->after('id');
            $table->string('role')->default('staff')->after('branch_id');
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('employee_code', 30)->nullable()->unique()->after('phone');
            $table->boolean('is_active')->default(true)->after('employee_code');
            $table->string('avatar_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['branch_id', 'role', 'phone', 'employee_code', 'is_active', 'avatar_url']);
        });
    }
};

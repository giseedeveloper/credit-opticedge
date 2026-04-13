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
        $existing = Schema::getColumnListing('customers');

        Schema::table('customers', function (Blueprint $table) use ($existing) {
            if (! in_array('loan_interest_rate', $existing, true)) {
                $table->decimal('loan_interest_rate', 5, 2)
                    ->nullable()
                    ->after('preferred_repayment');
            }

            if (! in_array('loan_interest_type', $existing, true)) {
                $table->string('loan_interest_type', 30)
                    ->nullable()
                    ->after('loan_interest_rate');
            }

            if (! in_array('loan_duration_weeks', $existing, true)) {
                $table->unsignedInteger('loan_duration_weeks')
                    ->nullable()
                    ->after('loan_interest_type');
            }

            if (! in_array('loan_grace_period_days', $existing, true)) {
                $table->unsignedInteger('loan_grace_period_days')
                    ->nullable()
                    ->after('loan_duration_weeks');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'loan_interest_rate',
                'loan_interest_type',
                'loan_duration_weeks',
                'loan_grace_period_days',
            ]);
        });
    }
};

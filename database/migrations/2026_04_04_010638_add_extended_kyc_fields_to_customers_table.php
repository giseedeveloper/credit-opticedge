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
            // Step 1 – Device extras
            if (! in_array('imei_2', $existing)) {
                $table->string('imei_2', 20)->nullable()->after('imei_number');
            }
            if (! in_array('serial_number', $existing)) {
                $table->string('serial_number', 50)->nullable()->after('imei_2');
            }
            if (! in_array('cash_price', $existing)) {
                $table->decimal('cash_price', 12, 2)->nullable()->after('serial_number');
            }
            if (! in_array('deposit_amount', $existing)) {
                $table->decimal('deposit_amount', 12, 2)->nullable()->after('cash_price');
            }
            if (! in_array('preferred_repayment', $existing)) {
                $table->string('preferred_repayment', 20)->nullable()->after('deposit_amount');
            }
            if (! in_array('device_box_photo_path', $existing)) {
                $table->string('device_box_photo_path')->nullable()->after('imei_photo_path');
            }
            if (! in_array('device_photo_path', $existing)) {
                $table->string('device_photo_path')->nullable()->after('device_box_photo_path');
            }

            // Step 2 – Identity
            if (! in_array('id_type', $existing)) {
                $table->string('id_type', 30)->nullable()->after('nida_number');
            }

            // Step 3 – Location
            if (! in_array('landmark', $existing)) {
                $table->string('landmark')->nullable()->after('district');
            }

            // Step 4 – Income & Work
            if (! in_array('work_location', $existing)) {
                $table->string('work_location')->nullable()->after('employer');
            }
            if (! in_array('income_payment_cycle', $existing)) {
                $table->string('income_payment_cycle', 20)->nullable()->after('monthly_expenses');
            }
            if (! in_array('duration_at_work', $existing)) {
                $table->string('duration_at_work', 60)->nullable()->after('income_payment_cycle');
            }
            if (! in_array('business_photo_path', $existing)) {
                $table->string('business_photo_path')->nullable()->after('client_fo_photo_path');
            }

            // Step 5 – Second NOK
            if (! in_array('nok2_name', $existing)) {
                $table->string('nok2_name')->nullable()->after('nok_relationship');
            }
            if (! in_array('nok2_phone', $existing)) {
                $table->string('nok2_phone', 20)->nullable()->after('nok2_name');
            }
            if (! in_array('nok2_relationship', $existing)) {
                $table->string('nok2_relationship', 60)->nullable()->after('nok2_phone');
            }

            // Step 6 – Consent
            if (! in_array('terms_accepted', $existing)) {
                $table->boolean('terms_accepted')->default(false)->after('nok2_relationship');
            }
            if (! in_array('data_consent_accepted', $existing)) {
                $table->boolean('data_consent_accepted')->default(false)->after('terms_accepted');
            }
            if (! in_array('call_consent_accepted', $existing)) {
                $table->boolean('call_consent_accepted')->default(false)->after('data_consent_accepted');
            }
            if (! in_array('consent_timestamp', $existing)) {
                $table->timestamp('consent_timestamp')->nullable()->after('call_consent_accepted');
            }

            // Step 7 – FO submission metadata
            if (! in_array('fo_notes', $existing)) {
                $table->text('fo_notes')->nullable()->after('consent_timestamp');
            }
            if (! in_array('application_source', $existing)) {
                $table->string('application_source', 60)->nullable()->after('fo_notes');
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
                'imei_2', 'serial_number', 'cash_price', 'deposit_amount', 'preferred_repayment',
                'device_box_photo_path', 'device_photo_path', 'id_type', 'landmark',
                'work_location', 'income_payment_cycle', 'duration_at_work', 'business_photo_path',
                'nok2_name', 'nok2_phone', 'nok2_relationship',
                'terms_accepted', 'data_consent_accepted', 'call_consent_accepted', 'consent_timestamp',
                'fo_notes', 'application_source',
            ]);
        });
    }
};

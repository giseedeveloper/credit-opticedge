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
        Schema::table('customers', function (Blueprint $table) {
            $table->tinyInteger('kyc_stage')->default(1)->after('kyc_status');

            // Stage 1 – device
            $table->string('imei_number', 20)->nullable()->after('kyc_stage');
            $table->string('device_specs')->nullable()->after('imei_number');
            $table->string('imei_photo_path')->nullable()->after('device_specs');

            // Stage 2 – extra financials & documents
            $table->decimal('monthly_expenses', 12, 2)->nullable()->after('monthly_income');
            $table->string('id_front_photo_path')->nullable()->after('monthly_expenses');
            $table->string('id_back_photo_path')->nullable()->after('id_front_photo_path');
            $table->string('headshot_photo_path')->nullable()->after('id_back_photo_path');
            $table->string('client_fo_photo_path')->nullable()->after('headshot_photo_path');

            // Stage 4 – next of kin
            $table->string('nok_name')->nullable()->after('client_fo_photo_path');
            $table->string('nok_phone', 20)->nullable()->after('nok_name');
            $table->string('nok_relationship')->nullable()->after('nok_phone');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'kyc_stage', 'imei_number', 'device_specs', 'imei_photo_path',
                'monthly_expenses', 'id_front_photo_path', 'id_back_photo_path',
                'headshot_photo_path', 'client_fo_photo_path',
                'nok_name', 'nok_phone', 'nok_relationship',
            ]);
        });
    }
};

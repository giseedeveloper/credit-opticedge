<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
        });
        Schema::table('inventory_units', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
        });
        Schema::table('commission_ledgers', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
        });
        Schema::table('vendor_wallets', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
        });

        DB::table('stock_transfers')
            ->where('to_type', 'App\\Models\\Vendor')
            ->update(['to_type' => 'App\\Models\\Dealer']);

        DB::table('stock_transfers')
            ->where('from_type', 'App\\Models\\Vendor')
            ->update(['from_type' => 'App\\Models\\Dealer']);

        Schema::rename('vendors', 'dealers');
        Schema::rename('vendor_wallets', 'dealer_wallets');

        Schema::table('dealer_wallets', function (Blueprint $table) {
            $table->renameColumn('vendor_id', 'dealer_id');
        });

        foreach (['loans', 'customers', 'inventory_units', 'commission_ledgers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('vendor_id', 'dealer_id');
            });
        }

        Schema::table('loans', function (Blueprint $table) {
            $table->foreign('dealer_id')->references('id')->on('dealers')->nullOnDelete();
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('dealer_id')->references('id')->on('dealers')->nullOnDelete();
        });
        Schema::table('inventory_units', function (Blueprint $table) {
            $table->foreign('dealer_id')->references('id')->on('dealers')->nullOnDelete();
        });
        Schema::table('commission_ledgers', function (Blueprint $table) {
            $table->foreign('dealer_id')->references('id')->on('dealers')->cascadeOnDelete();
        });
        Schema::table('dealer_wallets', function (Blueprint $table) {
            $table->foreign('dealer_id')->references('id')->on('dealers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dealer_wallets', function (Blueprint $table) {
            $table->dropForeign(['dealer_id']);
        });
        Schema::table('commission_ledgers', function (Blueprint $table) {
            $table->dropForeign(['dealer_id']);
        });
        Schema::table('inventory_units', function (Blueprint $table) {
            $table->dropForeign(['dealer_id']);
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['dealer_id']);
        });
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['dealer_id']);
        });

        foreach (['loans', 'customers', 'inventory_units', 'commission_ledgers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('dealer_id', 'vendor_id');
            });
        }

        Schema::table('dealer_wallets', function (Blueprint $table) {
            $table->renameColumn('dealer_id', 'vendor_id');
        });

        Schema::rename('dealers', 'vendors');
        Schema::rename('dealer_wallets', 'vendor_wallets');

        DB::table('stock_transfers')
            ->where('to_type', 'App\\Models\\Dealer')
            ->update(['to_type' => 'App\\Models\\Vendor']);

        DB::table('stock_transfers')
            ->where('from_type', 'App\\Models\\Dealer')
            ->update(['from_type' => 'App\\Models\\Vendor']);

        Schema::table('vendor_wallets', function (Blueprint $table) {
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
        });
        Schema::table('commission_ledgers', function (Blueprint $table) {
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
        });
        Schema::table('inventory_units', function (Blueprint $table) {
            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
        });
        Schema::table('loans', function (Blueprint $table) {
            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
        });
    }
};

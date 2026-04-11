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
        Schema::create('system_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->index();
            $table->string('title');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('mime_type', 120)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_documents');
    }
};

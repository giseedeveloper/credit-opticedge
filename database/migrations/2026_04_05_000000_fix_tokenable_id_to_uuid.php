<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The User model uses HasUuids, so tokenable_id must be a
     * char(36) column instead of the default bigint from morphs().
     */
    public function up(): void
    {
        if (! Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->uuidMorphs('tokenable');
                $table->text('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
            });
        } else {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->string('tokenable_id', 36)->change();
            });
        }
    }

    public function down(): void
    {
        // Only drop if we created it; otherwise revert the column change.
        Schema::dropIfExists('personal_access_tokens');
    }
};

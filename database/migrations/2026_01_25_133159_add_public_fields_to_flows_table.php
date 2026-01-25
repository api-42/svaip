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
        Schema::table('flows', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('description');
            $table->string('public_slug')->unique()->nullable()->after('is_public');
            $table->boolean('allow_anonymous')->default(true)->after('public_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flows', function (Blueprint $table) {
            $table->dropColumn(['is_public', 'public_slug', 'allow_anonymous']);
        });
    }
};

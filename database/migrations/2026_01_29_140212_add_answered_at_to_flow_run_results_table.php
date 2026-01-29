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
        Schema::table('flow_run_results', function (Blueprint $table) {
            $table->timestamp('answered_at')->nullable()->after('answer');
            $table->index('answered_at'); // For ordering queries by time
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flow_run_results', function (Blueprint $table) {
            $table->dropIndex(['answered_at']);
            $table->dropColumn('answered_at');
        });
    }
};

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
        Schema::table('flow_runs', function (Blueprint $table) {
            $table->index('flow_id');
            $table->index('completed_at');
            $table->index('created_at');
        });

        Schema::table('flow_run_results', function (Blueprint $table) {
            $table->index('flow_run_id');
            $table->index('card_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flow_runs', function (Blueprint $table) {
            $table->dropIndex(['flow_id']);
            $table->dropIndex(['completed_at']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('flow_run_results', function (Blueprint $table) {
            $table->dropIndex(['flow_run_id']);
            $table->dropIndex(['card_id']);
        });
    }
};

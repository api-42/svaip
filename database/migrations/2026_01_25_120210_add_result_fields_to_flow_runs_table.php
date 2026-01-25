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
            $table->integer('total_score')->default(0)->after('completed_at');
            $table->foreignId('result_template_id')->nullable()->constrained()->onDelete('set null')->after('total_score');
            $table->string('share_token')->unique()->nullable()->after('result_template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flow_runs', function (Blueprint $table) {
            $table->dropColumn(['total_score', 'result_template_id', 'share_token']);
        });
    }
};

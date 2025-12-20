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
        Schema::create('flow_run_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('answer');
            $table->foreignId('card_id')->constrained()->onDelete('cascade');
            $table->foreignId('flow_run_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flow_run_results');
    }
};

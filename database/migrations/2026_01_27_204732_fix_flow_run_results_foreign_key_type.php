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
        // SQLite doesn't support dropping columns, so we need to recreate the table
        Schema::dropIfExists('flow_run_results');
        
        Schema::create('flow_run_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('answer')->nullable();
            $table->foreignId('card_id')->constrained()->onDelete('cascade');
            
            // Fix: Use UUID instead of foreignId (BIGINT)
            $table->uuid('flow_run_id');
            $table->foreign('flow_run_id')
                  ->references('id')
                  ->on('flow_runs')
                  ->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to the old structure
        Schema::dropIfExists('flow_run_results');
        
        Schema::create('flow_run_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('answer')->nullable();
            $table->foreignId('card_id')->constrained()->onDelete('cascade');
            $table->foreignId('flow_run_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }
};

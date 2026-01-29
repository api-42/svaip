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
        Schema::create('flow_run_form_responses', function (Blueprint $table) {
            $table->id();
            $table->string('flow_run_id')->index();
            $table->string('field_name');
            $table->text('field_value')->nullable();
            $table->timestamps();
            
            // Foreign key with cascade delete
            $table->foreign('flow_run_id')
                ->references('id')
                ->on('flow_runs')
                ->onDelete('cascade');
            
            // Index for faster queries
            $table->index(['flow_run_id', 'field_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flow_run_form_responses');
    }
};

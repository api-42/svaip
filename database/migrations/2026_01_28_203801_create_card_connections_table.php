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
        Schema::create('card_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_card_id')->constrained('cards')->onDelete('cascade');
            $table->foreignId('target_card_id')->constrained('cards')->onDelete('cascade');
            $table->unsignedTinyInteger('source_option')->comment('0 = left/no, 1 = right/yes');
            $table->timestamps();
            
            // Ensure each option can only connect to one card
            $table->unique(['source_card_id', 'source_option']);
            
            // Index for reverse lookups (find all connections TO a card)
            $table->index('target_card_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_connections');
    }
};

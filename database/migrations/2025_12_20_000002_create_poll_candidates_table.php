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
        Schema::create('poll_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained('polls')->onDelete('cascade');
            $table->string('name'); // Nomzod nomi
            $table->text('description')->nullable(); // Nomzod haqida ma'lumot
            $table->string('photo')->nullable(); // Nomzod rasmi
            $table->integer('vote_count')->default(0); // Ovozlar soni
            $table->integer('order')->default(0); // Tartibi
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poll_candidates');
    }
};

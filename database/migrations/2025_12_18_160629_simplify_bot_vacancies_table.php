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
        Schema::table('bot_vacancies', function (Blueprint $table) {
            // Drop unnecessary columns
            $table->dropColumn([
                'original_vacancy_id',
                'salary_min',
                'salary_max',
                'work_type',
                'busyness_type',
                'description',
                'show_url',
                'raw_data',
                'district_soato',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bot_vacancies', function (Blueprint $table) {
            $table->string('original_vacancy_id')->nullable();
            $table->integer('salary_min')->nullable();
            $table->integer('salary_max')->nullable();
            $table->string('work_type')->nullable();
            $table->string('busyness_type')->nullable();
            $table->text('description')->nullable();
            $table->string('show_url')->nullable();
            $table->json('raw_data')->nullable();
            $table->string('district_soato', 20)->nullable();
        });
    }
};

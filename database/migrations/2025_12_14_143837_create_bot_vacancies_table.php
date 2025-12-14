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
        Schema::create('bot_vacancies', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('original_vacancy_id')->comment('oson-ish-api dagi ID');
            $table->string('source')->default('oson-ish')->comment('Qaysi platformadan');
            $table->enum('status', ['pending', 'approved', 'rejected', 'published'])->default('pending')->comment('Status');
            $table->string('title')->comment('Vakansiya nomi');
            $table->string('company_name')->nullable()->comment('Kompaniya nomi');
            $table->string('region_soato', 10)->nullable()->comment('Region SOATO');
            $table->string('region_name')->nullable()->comment('Region nomi');
            $table->string('district_soato', 10)->nullable()->comment('District SOATO');
            $table->string('district_name')->nullable()->comment('District nomi');
            $table->bigInteger('salary_min')->nullable()->comment('Minimum maosh');
            $table->bigInteger('salary_max')->nullable()->comment('Maximum maosh');
            $table->string('work_type')->nullable()->comment('Ish turi');
            $table->string('busyness_type')->nullable()->comment('Bandlik turi');
            $table->text('description')->nullable()->comment('Tavsif');
            $table->string('show_url')->nullable()->comment('Vacancy ko\'rish linki');
            $table->jsonb('raw_data')->nullable()->comment('To\'liq ma\'lumot');
            $table->bigInteger('management_message_id')->nullable()->comment('Boshqaruv kanalidagi message ID');
            $table->timestamp('published_at')->nullable()->comment('Kanalga yuborilgan vaqt');
            $table->timestamps();

            $table->index('original_vacancy_id');
            $table->index('status');
            $table->index('region_soato');
            $table->index('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_vacancies');
    }
};

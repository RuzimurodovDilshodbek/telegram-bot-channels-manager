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
        Schema::create('oson_ish_vacancies', function (Blueprint $table) {
            $table->id();
            $table->string('oson_ish_vacancy_id')->unique()->comment('Oson-Ish dagi vacancy ID');
            $table->string('company_tin')->nullable()->comment('Kompaniya STIR');
            $table->string('company_name')->comment('Kompaniya nomi');
            $table->integer('vacancy_status')->comment('Oson-Ish dagi holat (2 = faol)');
            $table->string('title')->comment('Vakansiya nomi');
            $table->integer('count')->default(1)->comment('Nechta xodim kerak');
            $table->string('payment_name')->nullable()->comment('To\'lov turi nomi');
            $table->string('work_name')->nullable()->comment('Ish turi nomi');
            $table->decimal('min_salary', 15, 2)->nullable()->comment('Minimal maosh');
            $table->decimal('max_salary', 15, 2)->nullable()->comment('Maksimal maosh');
            $table->string('work_experience_name')->nullable()->comment('Ish tajribasi nomi');
            $table->integer('age_from')->nullable()->comment('Yoshdan');
            $table->integer('age_to')->nullable()->comment('Yoshgacha');
            $table->integer('gender')->nullable()->comment('1-Erkak, 2-Ayol, 3-Ahamiyatsiz');
            $table->string('for_whos_name')->nullable()->comment('Kim uchun (nogironlar, yoshlar va h.k.)');
            $table->string('phone')->nullable()->comment('HR telefon raqami');
            $table->string('hr_fio')->nullable()->comment('HR FIO');
            $table->string('region_code')->nullable()->comment('Hudud kodi (SOATO)');
            $table->string('region_name')->nullable()->comment('Hudud nomi');
            $table->string('district_name')->nullable()->comment('Tuman nomi');
            $table->text('description')->nullable()->comment('Batafsil ma\'lumot');
            $table->string('show_url')->nullable()->comment('Oson-Ish dagi havola');
            $table->integer('previous_status')->nullable()->comment('Oldingi holat');
            $table->timestamp('status_changed_at')->nullable()->comment('Holat o\'zgardi vaqti');
            $table->timestamps();

            $table->index('vacancy_status');
            $table->index('oson_ish_vacancy_id');
            $table->index(['vacancy_status', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oson_ish_vacancies');
    }
};

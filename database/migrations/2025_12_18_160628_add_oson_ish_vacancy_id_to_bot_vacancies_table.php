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
            $table->string('oson_ish_vacancy_id')->nullable()->after('original_vacancy_id')->comment('Oson-Ish vacancy ID');
            $table->index('oson_ish_vacancy_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bot_vacancies', function (Blueprint $table) {
            $table->dropIndex(['oson_ish_vacancy_id']);
            $table->dropColumn('oson_ish_vacancy_id');
        });
    }
};

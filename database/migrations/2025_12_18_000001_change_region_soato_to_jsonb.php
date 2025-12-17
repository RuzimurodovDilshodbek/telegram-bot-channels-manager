<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, update existing data to wrap single values in an array  
        DB::unprepared("UPDATE channels SET region_soato = CASE WHEN region_soato IS NOT NULL AND region_soato != '' THEN ('[\"' || region_soato || '\"]')::jsonb ELSE '[]'::jsonb END");

        // Change column type to jsonb using raw SQL
        DB::unprepared('ALTER TABLE channels ALTER COLUMN region_soato TYPE jsonb USING region_soato::jsonb');
    }

    public function down(): void
    {
        // Convert back to string using raw SQL
        DB::unprepared('ALTER TABLE channels ALTER COLUMN region_soato TYPE varchar(10)');
    }
};

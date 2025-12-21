<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PostgreSQL uchun enum constraint ni o'zgartirish
        DB::statement("ALTER TABLE channels DROP CONSTRAINT IF EXISTS channels_type_check");
        DB::statement("ALTER TABLE channels ADD CONSTRAINT channels_type_check CHECK (type::text = ANY (ARRAY['management'::character varying, 'main'::character varying, 'region'::character varying, 'poll'::character varying]::text[]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eski holatga qaytarish
        DB::statement("ALTER TABLE channels DROP CONSTRAINT IF EXISTS channels_type_check");
        DB::statement("ALTER TABLE channels ADD CONSTRAINT channels_type_check CHECK (type::text = ANY (ARRAY['management'::character varying, 'main'::character varying, 'region'::character varying]::text[]))");
    }
};

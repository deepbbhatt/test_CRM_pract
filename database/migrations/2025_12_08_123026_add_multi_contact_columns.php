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
    Schema::table('contacts', function (Blueprint $table) {
        $table->json('additional_emails')->nullable(); // store array of strings
        $table->json('additional_phones')->nullable();
    });

    // Ensure custom_field_values exists with unique constraint
    Schema::table('customfield_values', function (Blueprint $table) {
        // if not already present, create unique index
        $table->unique(['contact_id','field_definition_id']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

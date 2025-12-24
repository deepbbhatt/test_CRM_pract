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
        Schema::create('contact_merges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_contact_id');
            $table->unsignedBigInteger('secondary_contact_id');
            $table->string('extra_email')->nullable();
            $table->string('extra_phone')->nullable();
            $table->timestamps();

            $table->foreign('master_contact_id')->references('id')->on('contacts');
            $table->foreign('secondary_contact_id')->references('id')->on('contacts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_merges');
    }
};

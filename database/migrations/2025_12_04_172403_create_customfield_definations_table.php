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
        Schema::create('customfield_definations', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // e.g., Birthday
                $table->string('slug')->unique();
                $table->string('type')->default('text'); // text, date, number, select, file, textarea
                $table->json('options')->nullable(); // for select/radio: JSON array
                $table->string('validation')->nullable(); // e.g., "nullable|date"
                $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customfield_definations');
    }
};

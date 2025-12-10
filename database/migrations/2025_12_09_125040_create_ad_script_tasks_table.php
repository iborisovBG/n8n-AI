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
        Schema::create('ad_script_tasks', function (Blueprint $table) {
            $table->id();
            $table->text('reference_script');
            $table->text('outcome_description');
            $table->text('new_script')->nullable();
            $table->text('analysis')->nullable();
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->text('error_details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_script_tasks');
    }
};

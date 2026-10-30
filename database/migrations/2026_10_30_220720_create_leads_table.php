<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Companies from directories that were never part of the list of companies to approach.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_key')->index();
            $table->string('source', 50);
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('web', 500)->nullable();
            $table->string('ico', 20)->nullable()->index();
            $table->string('employees', 50)->nullable();
            $table->unsignedBigInteger('revenue')->nullable();
            $table->date('founded_on')->nullable();
            $table->string('registry_status', 50)->nullable();
            $table->text('status_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};

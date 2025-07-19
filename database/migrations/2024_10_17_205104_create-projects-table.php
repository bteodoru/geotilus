<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('status', ['Active', 'On Hold', 'Done']);
            $table->text('description')->nullable();
            $table->foreignId('client_id')->constrained()->onDelete('cascade'); // Relație cu Client
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Relație cu User (dacă dorești să accesezi direct)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects');
    }
};

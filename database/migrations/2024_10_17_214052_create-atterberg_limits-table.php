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
        Schema::create('atterberg_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sample_id')->constrained()->onDelete('cascade');  // Relație cu Sample
            $table->float('liquid_limit', 5, 2)->nullable();  // Limita de lichiditate (%)
            $table->float('plastic_limit', 5, 2)->nullable();  // Limita de plasticitate (%)
            $table->float('shrinkage_limit', 5, 2)->nullable();  // Limita de contractie (%)
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
        Schema::dropIfExists('atterberg_limits');
    }
};

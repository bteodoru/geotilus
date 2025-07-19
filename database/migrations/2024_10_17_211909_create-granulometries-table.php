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
        Schema::create('granulometries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sample_id')->constrained()->onDelete('cascade');  // Relație cu Sample
            $table->float('clay', 5, 2)->nullable();  // Procentul de argilă
            $table->float('silt', 5, 2)->nullable();  // Procentul de praf
            $table->float('sand', 5, 2)->nullable();  // Procentul de nisip
            $table->float('gravel', 5, 2)->nullable();  // Procentul de pietriș
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
        Schema::dropIfExists('granulometries');
    }
};

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
        Schema::create('derived_soil_phase_indices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sample_id')->constrained()->onDelete('cascade');  // Relație cu Sample
            $table->float('dry_density', 5, 2)->nullable();
            $table->float('porosity', 5, 2)->nullable();
            $table->float('voids_ratio', 5, 2)->nullable();
            $table->float('moisture_content_at_saturation', 5, 2)->nullable();
            $table->float('degree_of_saturation', 5, 2)->nullable();
            $table->float('saturated_density', 5, 2)->nullable();
            $table->float('submerged_density', 5, 2)->nullable();
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
        Schema::dropIfExists('soil_phase_indices');
    }
};

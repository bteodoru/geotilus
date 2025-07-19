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
        Schema::table('granulometries', function (Blueprint $table) {
            $table->float('cobble', 5, 2)->after('gravel')->nullable();  // Procentul de bolovanis
            $table->float('boulder', 5, 2)->after('cobble')->nullable();  // Procentul de blocuri

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('granulometries', function (Blueprint $table) {
            //
        });
    }
};

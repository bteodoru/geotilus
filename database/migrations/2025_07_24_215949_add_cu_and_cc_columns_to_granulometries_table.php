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
            $table->float('cu', 5, 2)->after('boulder')->nullable();
            $table->float('cc', 5, 2)->after('boulder')->nullable();
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

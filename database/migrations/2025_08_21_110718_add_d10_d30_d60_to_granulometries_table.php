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
            $table->decimal('d10', 10, 6)->after('boulder')->nullable();
            $table->decimal('d30', 10, 6)->after('d10')->nullable();
            $table->decimal('d60', 10, 6)->after('d30')->nullable();
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
            $table->dropColumn(['d10', 'd30', 'd60']);
        });
    }
};

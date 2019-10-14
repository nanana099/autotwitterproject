<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsFlozenAndStoppedAtToOperationStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('operation_statuses', function (Blueprint $table) {
            $table->boolean('is_flozen')->default(false);
            $table->dateTime('stopped_at')->default(date('2000-01-01 00:00:00'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('operation_statuses', function (Blueprint $table) {
            $table->dropColumn('is_flozen');
            $table->dropColumn('stopped_at');
        });
    }
}

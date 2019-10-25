<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUnfollowCursorToOperationStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('operation_statuses', function (Blueprint $table) {
            $table->dropColumn('unfollowing_step');
            $table->dropColumn('unfollowing_target_account');
            $table->string('unfollowing_target_cursor')->default("-1");
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
            $table->integer('unfollowing_step')->default(0);
            $table->string('unfollowing_target_account')->default("");
            $table->dropColumn('unfollowing_target_cursor');
        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFollowedListRowsToOperationStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('operation_statuses', function (Blueprint $table) {
            $table->string('following_target_account')->default("");
            $table->string('following_target_account_cursor')->default("-1");
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
            $table->dropColumn('following_target_account');
            $table->dropColumn('following_target_account_cursor');
        });
    }
}

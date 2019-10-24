<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStoppedAtColumnToOperationStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('operation_statuses', function (Blueprint $table) {
            $table->dateTime('follow_stopped_at')->default(date('2000-01-01 00:00:00'));
            $table->dateTime('unfollow_stopped_at')->default(date('2000-01-01 00:00:00'));
            $table->dateTime('favorite_stopped_at')->default(date('2000-01-01 00:00:00'));
            $table->dateTime('tweet_stopped_at')->default(date('2000-01-01 00:00:00'));
            $table->dropColumn('stopped_at');
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
            $table->dropColumn('follow_stopped_at');
            $table->dropColumn('unfollow_stopped_at');
            $table->dropColumn('favorite_stopped_at');
            $table->dropColumn('tweet_stopped_at');
            $table->dateTime('stopped_at')->default(date('2000-01-01 00:00:00'));
        });
    }
}

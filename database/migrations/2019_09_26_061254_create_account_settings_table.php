<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('keyword_follow')->default('');
            $table->string('keyword_favorite')->default('');
            $table->integer('days_inactive_user')->default(15);
            $table->integer('days_unfollow_user')->default(7);
            $table->integer('num_max_unfollow_per_day')->default(1000);
            $table->integer('num_user_start_unfollow')->default(5000);
            $table->boolean('bool_unfollow_inactive')->default(false);
            $table->text('target_accounts');
            $table->unsignedBigInteger('account_id');
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_settings');
    }
}

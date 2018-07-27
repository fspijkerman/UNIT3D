<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlayersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('players', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->index();
            $table->integer('last_seen')->default(0);
            $table->integer('current_score')->default(0);
            $table->integer('high_score')->default(0);
            $table->integer('current_run')->default(0);
            $table->integer('best_run')->default(0);
            $table->integer('questions_answered')->default(0);
            $table->integer('playing_month')->default(0);
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
        Schema::dropIfExists('players');
    }
}

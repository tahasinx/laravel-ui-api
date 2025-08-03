<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v_codes', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_id')->unique();
            $table->string('user_id')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('code');
            $table->string('origin');
            $table->string('expired_date')->nullable();
            $table->string('expired_time')->nullable();
            $table->boolean('is_validated')->default(false);
            $table->boolean('is_expired')->default(false);
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
        Schema::dropIfExists('v_codes');
    }
}

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
        Schema::create('position_skill', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('position_id')->nullable(false);
            $table->foreign('position_id')
                ->references('id')
                ->on('positions')
                ->onDelete('cascade');
            $table->uuid('skill_id')->nullable(false);
            $table->foreign('skill_id')
                ->references('id')
                ->on('skills')
                ->onDelete('cascade');
            $table->timestamps();
            $table->unique(['position_id', 'skill_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('position_skill');
    }
};
